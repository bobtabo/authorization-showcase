import os
import requests
from django.http import HttpResponse, JsonResponse


def _auth_server_url() -> str:
    return os.environ.get('AUTH_SERVER_URL', 'http://host.docker.internal:8080/function/php')


def _proxy_get(request, path: str) -> HttpResponse:
    query = request.META.get('QUERY_STRING', '')
    url = _auth_server_url() + '/' + path
    if query:
        url += '?' + query

    headers = {'Accept': 'application/json'}
    auth = request.META.get('HTTP_AUTHORIZATION', '')
    if auth:
        headers['Authorization'] = auth

    try:
        resp = requests.get(url, headers=headers, timeout=10)
        return HttpResponse(resp.content, status=resp.status_code, content_type='application/json')
    except Exception as e:
        return JsonResponse({'error': str(e)}, status=502)


def health(request):
    return JsonResponse({'status': 'ok'})


def clients(request):
    return _proxy_get(request, 'clients')


def gate_issue(request):
    return _proxy_get(request, 'gate/issue')


def gate_verify(request, identifier: str):
    return _proxy_get(request, f'gate/client/{identifier}/verify')
