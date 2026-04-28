module.exports = {
  apps: [{
    name: 'monbeaupays-frontend',
    script: 'node',
    args: '.next/standalone/server.js',
    cwd: '/var/www/monbeaupays-frontend',
    instances: 1,
    exec_mode: 'fork',
    env: {
      NODE_ENV: 'production',
      PORT: 3000
    },
    error_file: '/var/log/pm2/monbeaupays-frontend-error.log',
    out_file: '/var/log/pm2/monbeaupays-frontend-out.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    merge_logs: true,
    autorestart: true,
    max_memory_restart: '1G',
    watch: false
  }]
};

