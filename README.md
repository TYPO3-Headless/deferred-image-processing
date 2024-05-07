# TYPO3 Deferred Image Processing for Frontend

> [!CAUTION]
> This is only a proof of concept and should not be used in production environments.

By default, TYPO3 processes images on-the-fly while rendering the page.\
This can lead to performance issues when a lot of images are displayed on a page, as each of them might require processing and resizing.\
The performance can be even more affected when for each image multiple sizes are generated.

To address this issue, the `Deferred Image Processing` feature was introduced for the TYPO3 frontend.

## How it works?

To handle deferred image processing, a new file processor was introduced: [DeferredFrontendImageProcessor](Classes/Resource/Processing/DeferredFrontendImageProcessor.php).\
This processor is responsible for either queuing image processing tasks in the database or processing the file immediately.\
Image processing tasks are queued when the image processing is requested for the first time (while page content is being generated) and the processed file does not yet exist.\
A corresponding record for the processed file is created inside the `sys_file_processedfile` table, but the physical file is not yet created.

When the web browser requests the image, the server (Nginx, Apache, etc.) checks if the file exists.\
If the file does not exist, the server rewrites the request to the TYPO3 index.php file.\
Next, TYPO3 checks through the dedicated middleware [DeferredImageProcessing](Classes/Middleware/DeferredImageProcessing.php) if it should handle the request.

This middleware checks if the `Deferred Image Processing` feature for the frontend is enabled and if the requested file is of a supported image type.\
It also checks if the requested file (by the full path) was enqueued for deferred processing.\
This check is done by verifying if a record with the file path (`public_url`) exists in the `sys_file_processedfile_queue` table.\
If the record exists, the middleware triggers the processing of the requested file by delegating the task to the dedicated file processor.

Finally, if the file is processed successfully, the middleware returns the processed file to the web browser.

## Required setup

All the deferred image processing tasks are queued in the database.\
It is required to have the dedicated database table for the queue, named `sys_file_processedfile_queue`.\
Make sure that the table is created before enabling the deferred image processing feature.

## Enable deferred image processing feature

The deferred frontend image processing is by default hidden behind a feature toggle.\
If you want to enable it, you have to set the following configuration in your `config/system/settings.php` or `config/system/additional.php` file:

```php
$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['deferredFrontendImageProcessing'] = true;
```

## Sample server configuration

### Apache (.htaccess)

Handle any request that contains the `_processed_` directory in the path and ends with an image file extension

```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*/_processed_/.+\.(gif|jpg|jpeg|png))$ index.php [L]
```

Handle any request that contains the path to default files storage processed directory and ends with an image file extension

```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^fileadmin/_processed_/.+\.(gif|jpg|jpeg|png)$ index.php [L]
```

Handle any request which contains the path to one of multiple storages and ends with an image file extension

> [!NOTE]
> You have to add all the paths to the storages which are used in your TYPO3 instance.


```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(fileadmin/_processed_|custom_storage/_processed_)/.+\.(gif|jpg|jpeg|png)$ index.php [L]
```

For detailed information about TYPO3 `.htaccess` configuration, please refer to the [official documentation](https://docs.typo3.org/p/lochmueller/staticfilecache/main/en-us/Configuration/Htaccess.html).

### Nginx

Handle any request which contains the `_processed_` directory in the path and ends with an image file extension.\
This is a very basic example and should be adjusted to your needs.

```nginx
location ~* ^/(.*)/_processed_/.+.(gif|jpg|jpeg|png)$ {
    # Try to serve the file if it exists, otherwise rewrite to index.php
    try_files $uri /index.php;
}
```

Handle any request which contains the path to default files storage processed directory and ends with an image file extension.

```nginx
location ~* ^/fileadmin/_processed_/.+.(gif|jpg|jpeg|png)$ {
    try_files $uri /index.php;
}
```

Handle any request which contains the path to one of multiple storages and ends with an image file extension.

> [!NOTE]
> You have to add all the paths to the storages which are used in your TYPO3 instance.

```nginx
location ~* ^/(fileadmin/_processed_|custom_storage/_processed_)/.+.(gif|jpg|jpeg|png)$ {
    try_files $uri /index.php;
}
```

## Development

### Run PHP unit tests

```bash
composer ci:test:unit
```
