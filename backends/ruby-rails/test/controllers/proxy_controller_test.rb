# This is a program developed by BobTabo.
#
# Copyright (c) 2026 BobTabo. All Rights Reserved.

require 'test_helper'
require 'net/http'

class ProxyControllerTest < ActionController::TestCase
  BEARER_TOKEN = 'Bearer 0036f13f53d29672eed54e4ab1672edeab482d49e77b626c4a1b110e45e46369'
  IDENTIFIER   = 'alpha-tech'
  MEMBER       = 'M000001'

  def setup
    @controller = ProxyController.new
  end

  test 'health returns 200 with status ok' do
    get :health
    assert_response :success
    body = JSON.parse(response.body)
    assert_equal 'ok', body['status']
  end

  test 'clients returns non-empty JSON array' do
    @request.headers['Authorization'] = BEARER_TOKEN
    get :clients, params: { 'statuses[]' => '2' }

    assert_response :success
    data = JSON.parse(response.body)
    assert data.is_a?(Array), "expected Array, got #{data.class}"
    assert data.length > 0, 'expected at least one client'
  end

  test 'gate_issue returns a token' do
    @request.headers['Authorization'] = BEARER_TOKEN
    get :gate_issue, params: { member: MEMBER }

    assert_response :success
    body = JSON.parse(response.body)
    assert body.key?('token'), "expected 'token' in response: #{response.body}"
    assert body['token'].present?
  end

  test 'gate_verify returns payload for valid JWT' do
    @request.headers['Authorization'] = BEARER_TOKEN
    get :gate_issue, params: { member: MEMBER }
    assert_response :success
    jwt = JSON.parse(response.body)['token']

    @request.headers['Authorization'] = BEARER_TOKEN
    get :gate_verify, params: { identifier: IDENTIFIER, token: jwt }

    assert_response :success
  end

  test 'proxy returns 502 when upstream is unreachable' do
    orig = ENV['AUTH_SERVER_URL']
    ENV['AUTH_SERVER_URL'] = 'http://127.0.0.1:1'
    @request.headers['Authorization'] = BEARER_TOKEN
    get :clients
    assert_response :bad_gateway
  ensure
    ENV['AUTH_SERVER_URL'] = orig
  end
end
