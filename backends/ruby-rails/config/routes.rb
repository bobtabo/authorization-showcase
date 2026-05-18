Rails.application.routes.draw do
  get "up" => "rails/health#show", as: :rails_health_check

  get 'health',                               to: 'proxy#health'
  get 'clients',                              to: 'proxy#clients'
  get 'gate/issue',                           to: 'proxy#gate_issue'
  get 'gate/client/:identifier/verify',       to: 'proxy#gate_verify'
end
