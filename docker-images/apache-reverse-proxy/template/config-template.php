<?php
        $ip_static = getenv('STATIC_APP');
        $ip_dynamic = getenv("DYNAMIC_APP");
?>

<VirtualHost *:80>
        ServerName test.res.ch

        #ErrorLog ${APACHE_LOG_DIR}/error.log
        #CustomLog ${APACHE_LOG_DIR}/access.log combined

        ProxyPass '/api/students/' 'http://<?php print "$ip_dynamic";?>/'
        ProxyPassReverse '/api/sutdents' 'http://<?php print "$ip_dynamic";?>/'

        ProxyPass '/' 'http://<?php print "$ip_static";?>/'
        ProxyPassReverse '/' 'http://<?php print "$ip_static";?>/'

</VirtualHost>