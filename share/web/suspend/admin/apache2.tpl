# Rendered for suspended domains in every web model - not user-selectable

<VirtualHost %vhost%>

    ServerName %domain_idn%
    IncludeOptional %home%/%user%/conf/web/%domain%/botlimit.apache2.conf*
    %alias_string%
    ServerAdmin %email%
    DocumentRoot %docroot%
    CustomLog /var/log/%web_system%/domains/%domain%.bytes bytes
    CustomLog /var/log/%web_system%/domains/%domain%.log combined
    ErrorLog /var/log/%web_system%/domains/%domain%.error.log

    IncludeOptional %home%/%user%/conf/web/%domain%/apache2.forcessl.conf*

    <Directory %docroot%>
        AllowOverride All
        Options -Indexes
        # page dir is outside the granted /home paths
        Require all granted
    </Directory>

    IncludeOptional /etc/apache2/conf.d/*.inc
</VirtualHost>
#=HESTIARE-SSL-VHOST=#
# Rendered for suspended domains in every web model - not user-selectable

<VirtualHost %vhost_ssl%>

    ServerName %domain_idn%
    IncludeOptional %home%/%user%/conf/web/%domain%/botlimit.apache2.conf*
    %alias_string%
    ServerAdmin %email%
    DocumentRoot %docroot%
    CustomLog /var/log/%web_system%/domains/%domain%.bytes bytes
    CustomLog /var/log/%web_system%/domains/%domain%.log combined
    ErrorLog /var/log/%web_system%/domains/%domain%.error.log

    <Directory %docroot%>
        AllowOverride All
        SSLRequireSSL
        Options -Indexes
        # page dir is outside the granted /home paths
        Require all granted
    </Directory>
    SSLEngine on
    SSLVerifyClient none
    SSLCertificateFile %ssl_crt%
    SSLCertificateKeyFile %ssl_key%
    %ssl_ca_str%SSLCertificateChainFile %ssl_ca%

    IncludeOptional /etc/apache2/conf.d/*.inc
</VirtualHost>
