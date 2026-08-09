nginx配置
```
server {
    listen 80;
    listen 443 ssl http2;
    server_name 154.44.25.4 iot-admin.zuoyebang.com;

    ssl_certificate certs/2.crt;
    ssl_certificate_key certs/2.key;

    include log.conf;
    include acme_challenge.conf;

    root conf/statics/15;

    location ~ \.php$ {
        fastcgi_pass svc-php-static-website-luheqs:9000;
        fastcgi_index index.php;
        include /usr/local/openresty/nginx/conf/fastcgi_params;
        fastcgi_param SCRIPT_FILENAME /var/www/15/$fastcgi_script_name;
        fastcgi_connect_timeout 60;
        fastcgi_send_timeout 60;
        fastcgi_read_timeout 60;
    }

    location / {
        rewrite_by_lua_file /opt/om/nginx/lua/ngx_conf_rewrite.lua;
        header_filter_by_lua_file /opt/om/nginx/lua/ngx_conf_resp_header.lua;
        body_filter_by_lua_file /opt/om/nginx/lua/ngx_conf_resp_body.lua;
        try_files $uri $uri/ @fallback;
        index index.htm index.html index.php;
    }

    include robot.conf;
    include auth.conf;

    location = /403.html {
        root html;
        internal;
    }

    location @fallback {
        set $fallback_file $uri;
        if (-f $document_root$fallback_file.php) {
            rewrite ^(.*)$ $1.php last;
        }
        if (!-f $document_root$fallback_file) {
            rewrite ^/(.*)$ /proxy/$1 last;
        }
        return 404;
    }

    location /proxy/ {
        proxy_pass http://iot-admin.zuoyebang.com/;
        proxy_set_header HOST iot-admin.zuoyebang.com;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        internal;
    }


    include error_page.conf;
}
```
