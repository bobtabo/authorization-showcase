"""
proxy.urls — プロキシアプリの URL ルーティング設定モジュール。

Author: Satoshi Nagashiba <satoshi.nagashiba@gmail.com>
"""
from django.urls import path
from . import views

urlpatterns = [
    path('health', views.health),
    path('clients', views.clients),
    path('gate/issue', views.gate_issue),
    path('gate/client/<str:identifier>/verify', views.gate_verify),
]
