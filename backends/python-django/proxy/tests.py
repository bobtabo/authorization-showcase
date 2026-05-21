"""
proxy.tests — プロキシビューの単体テストモジュール。

Author: Satoshi Nagashiba <satoshi.nagashiba@gmail.com>
"""
import json
from unittest.mock import MagicMock, patch

from django.test import Client, TestCase


def _make_response(body, status_code=200):
    mock_resp = MagicMock()
    mock_resp.status_code = status_code
    mock_resp.content = body if isinstance(body, bytes) else body.encode()
    return mock_resp


class HealthViewTest(TestCase):
    def test_health_returns_ok(self):
        client = Client()
        response = client.get('/health')

        self.assertEqual(response.status_code, 200)
        data = json.loads(response.content)
        self.assertEqual(data['status'], 'ok')


class ClientsViewTest(TestCase):
    @patch('proxy.views.requests.get')
    def test_clients_returns_upstream_body(self, mock_get):
        mock_get.return_value = _make_response(b'[{"id":1},{"id":2}]')

        response = Client().get('/clients')

        self.assertEqual(response.status_code, 200)
        self.assertEqual(json.loads(response.content), [{'id': 1}, {'id': 2}])

    @patch('proxy.views.requests.get')
    def test_clients_forwards_auth_header(self, mock_get):
        mock_get.return_value = _make_response(b'[]')

        Client().get('/clients', HTTP_AUTHORIZATION='Bearer test-token')

        _, kwargs = mock_get.call_args
        self.assertEqual(kwargs['headers']['Authorization'], 'Bearer test-token')

    @patch('proxy.views.requests.get')
    def test_clients_forwards_query_string(self, mock_get):
        mock_get.return_value = _make_response(b'[]')

        Client().get('/clients?page=2&limit=10')

        called_url = mock_get.call_args[0][0]
        self.assertIn('page=2', called_url)
        self.assertIn('limit=10', called_url)


class GateIssueViewTest(TestCase):
    @patch('proxy.views.requests.get')
    def test_gate_issue_proxies(self, mock_get):
        mock_get.return_value = _make_response(b'{"token":"abc"}')

        response = Client().get('/gate/issue')

        self.assertEqual(response.status_code, 200)
        called_url = mock_get.call_args[0][0]
        self.assertIn('api/gate/issue', called_url)


class GateVerifyViewTest(TestCase):
    @patch('proxy.views.requests.get')
    def test_gate_verify_includes_identifier(self, mock_get):
        mock_get.return_value = _make_response(b'{"valid":true}')

        response = Client().get('/gate/client/alpha-tech/verify')

        self.assertEqual(response.status_code, 200)
        called_url = mock_get.call_args[0][0]
        self.assertIn('alpha-tech', called_url)


class ProxyErrorHandlingTest(TestCase):
    @patch('proxy.views.requests.get')
    def test_proxy_returns_502_on_exception(self, mock_get):
        mock_get.side_effect = Exception('connection refused')

        response = Client().get('/clients')

        self.assertEqual(response.status_code, 502)
        data = json.loads(response.content)
        self.assertIn('error', data)
