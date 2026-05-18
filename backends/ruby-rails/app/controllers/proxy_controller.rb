require 'net/http'
require 'uri'

class ProxyController < ApplicationController
  skip_before_action :verify_authenticity_token

  def health
    render json: { status: 'ok' }
  end

  def clients
    proxy_get('clients')
  end

  def gate_issue
    proxy_get('gate/issue')
  end

  def gate_verify
    proxy_get("gate/client/#{params[:identifier]}/verify")
  end

  private

  def auth_server_url
    ENV['AUTH_SERVER_URL'] || 'http://host.docker.internal:8080/function/php'
  end

  def proxy_get(path)
    query = request.query_string
    uri = URI("#{auth_server_url}/#{path}#{query.present? ? "?#{query}" : ''}")

    http = Net::HTTP.new(uri.host, uri.port)
    req = Net::HTTP::Get.new(uri)
    req['Accept'] = 'application/json'
    auth = request.headers['Authorization']
    req['Authorization'] = auth if auth.present?

    resp = http.request(req)
    render plain: resp.body, status: resp.code.to_i, content_type: 'application/json'
  rescue StandardError => e
    render json: { error: e.message }, status: :bad_gateway
  end
end
