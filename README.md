## About the project
This project was created to extract some information about DNS and subdomains that exist in many txt file and unified it into a excel file. 

## Run Command to Process DNS Files

To run the command that generates the excel file containing the DNS selected from the txt files just need to run the following command in the root path of the project: 

```php artisan app:process-files```

The excel file should be created in ```storage/app/dns-unified.xlsx```

And the txt files are in ```/Users/carlos.saborio/Documents/laravel/dns-proccesor-app/resources/data```
