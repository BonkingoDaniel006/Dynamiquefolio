#!/bin/bash
# Remplacer le port 80 par le port attribué par Render dans la config Apache
sed -i "s/80/$PORT/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Lancer Apache en premier plan
apache2-foreground