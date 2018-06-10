
<?php
        $ip_static = getenv('STATIC_APP');
        $ip_static2 = getenv('STATIC_APP2');
        $ip_dynamic = getenv('DYNAMIC_APP');
        $ip_dynamic2 = getenv('DYNAMIC_APP2');
?>

<VirtualHost *:80>
        ServerName test.res.ch

        <Proxy balancer://staticclust>
                # Static
                BalancerMember 'http://<?php print "$ip_static"?>'
                BalancerMember 'http://<?php print "$ip_static2"?>'

                ProxySet lbmethod=byrequests
        </Proxy>


        <Proxy balancer://dynclust>
                BalancerMember 'http://<?php print "$ip_dynamic"?>'
                BalancerMember 'http://<?php print "$ip_dynamic2"?>'

                ProxySet lbmethod=byrequests
        </Proxy>

        <Location /balancer-manager>
            SetHandler balancer-manager
        </Location>

        ProxyPass '/balancer-manager' '!'

        ProxyPass '/api/students/' 'balancer://dynclust/'
        ProxyPassReverse '/api/students/' 'balancer://dynclust/'

        ProxyPass '/' 'balancer://staticclust/'
        ProxyPassReverse '/' 'balancer://staticclust/'
        

</VirtualHost>
