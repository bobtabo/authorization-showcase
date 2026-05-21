# frozen_string_literal: true
#
# This is a program developed by BobTabo.
#
# Copyright (c) 2026 BobTabo. All Rights Reserved.

require 'net/http'
require 'uri'

# This is a program developed by BobTabo.
#
# Copyright (c) 2026 BobTabo. All Rights Reserved.
#
# 認可サーバーへのリバースプロキシを提供するコントローラークラスです。
# @author Satoshi Nagashiba <satoshi.nagashiba@gmail.com>
class ProxyController < ApplicationController
  skip_before_action :verify_authenticity_token, raise: false

  # ヘルスチェック応答を返します。
  def health
    render json: { status: 'ok' }
  end

  # クライアント一覧を認可サーバーから取得して返します。
  def clients
    proxy_get('api/clients')
  end

  # クライアント会員向け JWT を発行して返します。
  def gate_issue
    proxy_get('api/gate/issue')
  end

  # JWT を検証してペイロードを返します。
  def gate_verify
    proxy_get("api/gate/client/#{params[:identifier]}/verify")
  end

  private

  # 認可サーバーの URL を取得します。
  def auth_server_url
    ENV['AUTH_SERVER_URL'] || 'http://host.docker.internal:8080/function/php'
  end

  # 指定パスへ GET リクエストを転送し、認可サーバーのレスポンスをそのまま返します。
  def proxy_get(path)
    query = request.query_string
    uri = URI("#{auth_server_url}/#{path}#{query.present? ? "?#{query}" : ''}")

    http = Net::HTTP.new(uri.host, uri.port)
    http.use_ssl = uri.scheme == 'https'
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
