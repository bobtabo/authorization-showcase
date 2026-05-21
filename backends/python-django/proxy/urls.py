"""
This is a program developed by BobTabo.

Copyright (c) 2026 BobTabo. All Rights Reserved.
"""
from django.urls import path
from . import views

urlpatterns = [
    path('health', views.health),
    path('clients', views.clients),
    path('gate/issue', views.gate_issue),
    path('gate/client/<str:identifier>/verify', views.gate_verify),
]
