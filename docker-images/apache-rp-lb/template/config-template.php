
<?php
        // BALANCER_WORKER_ROUTE est la route du membre du groupe de répartition de charge qui sera utilisé pour la requête courante.
        // BALANCE_ROUTE_CHANGED vaut 1 si la route de session n'est pas la même que la route 

        // Récupération des adresses ips
        $ip_static = getenv('STATIC_APP');
        $ip_static2 = getenv('STATIC_APP2');
        $ip_dynamic = getenv('DYNAMIC_APP');
        $ip_dynamic2 = getenv('DYNAMIC_APP2');
?>

<VirtualHost *:80>
        ServerName test.res.ch

        
        Header add Set-Cookie "ROUTEID=.%{BALANCER_WORKER_ROUTE}e; path=/" env=BALANCER_ROUTE_CHANGED

        <Proxy balancer://staticclust>
                # Static
                BalancerMember 'http://<?php print "$ip_static"?>'  route=node1
                BalancerMember 'http://<?php print "$ip_static2"?>' route=node2

                ProxySet lbmethod=byrequests
                ProxySet stickysession=ROUTEID
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
