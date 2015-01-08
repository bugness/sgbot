#SGBot

====

##Installation:

--

```
git clone https://github.com/bugness/sgbot.git

curl -sS https://getcomposer.org/installer | php

composer install

cp app/config.sample.yml app/config.your-name.yml

chmod +x app/console

app/console app:exec app/config.your-name.yml
```
