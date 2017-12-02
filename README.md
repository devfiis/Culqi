## Payment processor Culqi, plugin for magento 2

## Step 1
Add the project composer
```bash
"require": {
    "culqi/culqi-php": "1.3.3"
}
```

## Step 2
#### Option 2.1
Install plugin in mode developer

```bash
cd your_project_path/
sudo php bin/magento deploy:mode:set developer
sudo php bin/magento setup:upgrade
sudo php bin/magento setup:di:compile
sudo php bin/magento setup:static-content:deploy -f
sudo php bin/magento cache:clean
```

#### Option 2.2
Install plugin in mode production
```bash
cd your_project_path/
sudo php bin/magento deploy:mode:set production
sudo php bin/magento maintenance:enabled
sudo php bin/magento setup:upgrade
sudo php bin/magento setup:di:compile
sudo php bin/magento setup:static-content:deploy --skip-compilation
sudo php bin/magento cache:clean
sudo chmod -R 777 var/
sudo chmod -R 777 pub/
sudo php bin/magento maintenance:disable
```
## Step 3
Create an account in https://www.culqi.com and generate the credentials

## Step 4
Configure the credentials in the payment method section of magento project
