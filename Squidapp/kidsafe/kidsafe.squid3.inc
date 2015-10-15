# This file is enabled by adding entry following entry near to the top of /etc/squid3/squid.conf 
# include /opt/kidsafe/kidsafe.squid3.inc


# Kidsafe application helper
# -----------------------------------------------------------------------------
external_acl_type kidsafe_app negative_ttl=0 %SRC %DST %PORT %PATH /opt/kidsafe/kidsafe.py
acl kidsafe_acl external kidsafe_app

# LOCAL NETWORK
# -----------------------------------------------------------------------------
# change to your own local network here if doesn't begin 192.168
# access to any addresses listed here will be allowed and will not be verified using kidsafe and so won't be included in the kidsafe log 
acl local_acl dst 192.168.0.0/16
# enter the local ip address of this proxy server below
acl localhost_acl src 127.0.0.1/32 ::1

# ACCESS RULES
# -----------------------------------------------------------------------------
# localhost can bypass the proxy anyway so we can allow them anywhere
http_access allow localhost_acl	
# allow local access (eg. to error page), although client is normally configured to bypass anyway
http_access allow local_acl  
# validate all other requests
http_access allow kidsafe_acl
# block all sites that have a deny
http_access deny all 

# REDIRECT TO WEB LOGIN / CONFIG PAGE
# -----------------------------------------------------------------------------
# set this address to the address of the proxy server
# squid 3.1 only supports %s option - &s=%i (client address) could be used for 3.2
# otherwise let php get client address (needs bypass proxy for local in client config)
deny_info http://192.168.0.3/kidsafe/blocked.php?url=%s all


