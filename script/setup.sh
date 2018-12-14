#!/bin/bash

# Proper group should be insert here
GRP="www-data"
PERM=0775

sudo chgrp -R $GRP  translations/
sudo chmod -R $PERM translations/
sudo chgrp -R $GRP  data/
sudo chmod -R $PERM data/
sudo mkdir -p var/translations/
sudo mkdir -p var/data/
sudo chgrp -R $GRP  var/
sudo chmod -R $PERM var/
sudo chgrp -R $GRP  public/image/product/
sudo chmod -R $PERM public/image/product/

sudo mkdir -p private/
sudo chgrp -R $GRP  private/
sudo chmod -R $PERM private/

sudo mkdir -p private/attachments/
sudo chgrp -R $GRP  private/attachments/
sudo chmod -R $PERM private/attachments/

sudo mkdir -p private/updates/
sudo chgrp -R $GRP  private/updates/
sudo chmod -R $PERM private/updates/

sudo apt update
sudo apt install -y xvfb libfontconfig wkhtmltopdf php7.0-apcu
