"""
This is a program developed by BobTabo.

Copyright (c) 2026 BobTabo. All Rights Reserved.
"""
import json
import os

from django.test import Client, TestCase

BEARER_TOKEN = 'Bearer 0036f13f53d29672eed54e4ab1672edeab482d49e77b626c4a1b110e45e46369'
IDENTIFIER   = 'alpha-tech'
MEMBER       = 'M000001'


class HealthViewTest(TestCase):
    def test_health_returns_ok(self):
        response = Client().get('/health')

        self.assertEqual(response.status_code, 200)
        data = json.loads(response.content)
        self.assertEqual(data['status'], 'ok')


class ClientsViewTest(TestCase):
    def test_clients_returns_list(self):
        response = Client().get('/clients?statuses[]=2', HTTP_AUTHORIZATION=BEARER_TOKEN)

        self.assertEqual(response.status_code, 200)
        data = json.loads(response.content)
        self.assertIsInstance(data['data'], list)
        self.assertGreater(len(data['data']), 0)


class GateIssueViewTest(TestCase):
    def test_gate_issue_returns_token(self):
        response = Client().get(f'/gate/issue?member={MEMBER}', HTTP_AUTHORIZATION=BEARER_TOKEN)

        self.assertEqual(response.status_code, 200)
        data = json.loads(response.content)
        self.assertIn('token', data)
        self.assertTrue(data['token'])


class GateVerifyViewTest(TestCase):
    def test_gate_verify_returns_payload(self):
        issue_resp = Client().get(f'/gate/issue?member={MEMBER}', HTTP_AUTHORIZATION=BEARER_TOKEN)
        self.assertEqual(issue_resp.status_code, 200)
        jwt = json.loads(issue_resp.content)['token']

        verify_resp = Client().get(
            f'/gate/client/{IDENTIFIER}/verify?token={jwt}',
            HTTP_AUTHORIZATION=BEARER_TOKEN,
        )

        self.assertEqual(verify_resp.status_code, 200)


class UpstreamErrorTest(TestCase):
    def setUp(self):
        self._orig = os.environ.get('AUTH_SERVER_URL')
        os.environ['AUTH_SERVER_URL'] = 'http://127.0.0.1:1'

    def tearDown(self):
        if self._orig is not None:
            os.environ['AUTH_SERVER_URL'] = self._orig
        else:
            os.environ.pop('AUTH_SERVER_URL', None)

    def test_proxy_returns_502_on_upstream_error(self):
        response = Client().get('/clients', HTTP_AUTHORIZATION=BEARER_TOKEN)

        self.assertEqual(response.status_code, 502)
        data = json.loads(response.content)
        self.assertIn('error', data)
