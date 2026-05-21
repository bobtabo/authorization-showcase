require 'test_helper'
require 'net/http'

class ProxyControllerTest < ActionController::TestCase
  def setup
    @controller = ProxyController.new
  end

  # ---------------------------------------------------------------------------
  # Health endpoint — no upstream call, purely local
  # ---------------------------------------------------------------------------

  test 'health returns 200 with status ok' do
    get :health
    assert_response :success
    body = JSON.parse(response.body)
    assert_equal 'ok', body['status']
  end

  # ---------------------------------------------------------------------------
  # Helpers to build a fake Net::HTTP response
  # ---------------------------------------------------------------------------

  def fake_response(body:, code:)
    resp = Object.new
    resp.define_singleton_method(:body) { body }
    resp.define_singleton_method(:code) { code.to_s }
    resp
  end

  def fake_http(response)
    http = Object.new
    http.define_singleton_method(:request) { |_req| response }
    http
  end

  # ---------------------------------------------------------------------------
  # /clients
  # ---------------------------------------------------------------------------

  test 'clients returns upstream body with correct status' do
    upstream = fake_response(body: '[{"id":1}]', code: 200)

    Net::HTTP.stub(:new, ->(_host, _port) { fake_http(upstream) }) do
      get :clients
    end

    assert_response :success
    assert_equal '[{"id":1}]', response.body
  end

  test 'clients forwards Authorization header to upstream' do
    received_headers = {}
    upstream = fake_response(body: '{}', code: 200)

    capturing_http = Object.new
    capturing_http.define_singleton_method(:request) do |req|
      received_headers['Authorization'] = req['Authorization']
      upstream
    end

    Net::HTTP.stub(:new, ->(_host, _port) { capturing_http }) do
      @request.headers['Authorization'] = 'Bearer test-token'
      get :clients
    end

    assert_equal 'Bearer test-token', received_headers['Authorization']
  end

  test 'clients includes query string in upstream URI' do
    called_uri = nil
    upstream   = fake_response(body: '{}', code: 200)

    capturing_http = Object.new
    capturing_http.define_singleton_method(:request) do |req|
      called_uri = req.path
      upstream
    end

    Net::HTTP.stub(:new, ->(_host, _port) { capturing_http }) do
      get :clients, params: { foo: 'bar' }
    end

    assert_match(/foo=bar/, called_uri.to_s)
  end

  # ---------------------------------------------------------------------------
  # /gate/issue
  # ---------------------------------------------------------------------------

  test 'gate_issue proxies to api/gate/issue path' do
    called_path = nil
    upstream    = fake_response(body: '{"token":"jwt"}', code: 200)

    capturing_http = Object.new
    capturing_http.define_singleton_method(:request) do |req|
      called_path = req.path
      upstream
    end

    Net::HTTP.stub(:new, ->(_host, _port) { capturing_http }) do
      get :gate_issue
    end

    assert_match(%r{api/gate/issue}, called_path.to_s)
    assert_response :success
    assert_equal '{"token":"jwt"}', response.body
  end

  # ---------------------------------------------------------------------------
  # /gate/client/:identifier/verify
  # ---------------------------------------------------------------------------

  test 'gate_verify includes identifier in upstream path' do
    called_path = nil
    upstream    = fake_response(body: '{"valid":true}', code: 200)

    capturing_http = Object.new
    capturing_http.define_singleton_method(:request) do |req|
      called_path = req.path
      upstream
    end

    Net::HTTP.stub(:new, ->(_host, _port) { capturing_http }) do
      get :gate_verify, params: { identifier: 'client-abc' }
    end

    assert_match(%r{api/gate/client/client-abc/verify}, called_path.to_s)
    assert_response :success
  end

  # ---------------------------------------------------------------------------
  # Error handling — upstream raises → 502
  # ---------------------------------------------------------------------------

  test 'proxy returns 502 when upstream raises a network error' do
    exploding_http = Object.new
    exploding_http.define_singleton_method(:request) do |_req|
      raise SocketError, 'connection refused'
    end

    Net::HTTP.stub(:new, ->(_host, _port) { exploding_http }) do
      get :clients
    end

    assert_response :bad_gateway
    body = JSON.parse(response.body)
    assert body.key?('error')
    assert_match(/connection refused/, body['error'])
  end
end
