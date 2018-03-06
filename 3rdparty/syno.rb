require 'httparty'
require 'concurrent'
require 'thread'
require 'time'
require 'fileutils' 

SYNO_AUTH_API = "/webapi/auth.cgi?api=SYNO.API.Auth&method=Login&version=1&account=#{ARGV[2]}&passwd=#{ARGV[3]}&session=SurveillanceStation"
SYNO_SESSION_LOGOUT = "/webapi/auth.cgi?api=SYNO.API.Auth&method=Logout&version=1&session=SurveillanceStation";

SYNO_SNAPSHOT =
    {
        'v6' => "/webapi/SurveillanceStation/camera.cgi?api=SYNO.SurveillanceStation.Camera&method=GetSnapshot&version=1&cameraId=synoCameraId",
        'v7' => "/webapi/entry.cgi?api=SYNO.SurveillanceStation.Camera&method=GetSnapshot&version=1&cameraId=synoCameraId"
    }

class SurveillanceStation
  include HTTParty
  base_uri "#{ARGV[0]}:#{ARGV[1]}"

  #############################
  ######SYNOLOGY SNAPSHOT######
  #############################

  def take_snapshot(camera,time)
    File.open("/tmp/#{camera.split('%')[0]}_#{time.strftime("%Y-%m-%d_%Hh%Mm%Ss")}.jpg", "wb") do |temp|
      temp.write self.class.get(SYNO_SNAPSHOT[@version].gsub("synoCameraId",camera.split('%')[1]),:headers => {'Cookie' => @cookies}).parsed_response
    end
    value =  "#{camera.split('%')[0]}_#{time.strftime("%Y-%m-%d_%Hh%Mm%Ss")}.jpg"
  end

  def snapshot
    self.auth
    snapshots = []
    time = Time.now
    @version = ARGV[4]
    ARGV[5].split(',').each do |camera|
      snapshots << Concurrent::Future.execute{ take_snapshot(camera,time) }
    end

    while (snapshots.any? {|p| p.state != :fulfilled})
      sleep 1
    end
    self.logout

    FileUtils.mkdir_p "/tmp/#{time.strftime("%Y-%m-%d_%H_%M_%S")}"

    snapshots.each do |future|
      FileUtils.mv("/tmp/#{future.value}", "/tmp/#{time.strftime("%Y-%m-%d_%H_%M_%S")}/#{future.value}")
    end

    puts "/tmp/#{time.strftime("%Y-%m-%d_%H_%M_%S")}"

  end

  def auth
    authRequest = self.class.get(SYNO_AUTH_API)
    @cookies =  authRequest.headers['Set-Cookie']
  end

  def logout
    self.class.get(SYNO_SESSION_LOGOUT,:headers => {'Cookie' => @cookies})
  end

end

syno = SurveillanceStation.new()
syno.snapshot