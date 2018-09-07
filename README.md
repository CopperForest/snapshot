# snapshot
This composer plugin let you choose differents code snapshots for differents users.

## implements the SessionInterface
The first step to use the snashots system is ensure that you **SessionHandler** class implements the interface **copperforest\snapshot\authentication\SessionHandlerInterface**.
For this you must create the next five methods:

* **getUserId()**: must return the ID of the current user.
* **setPreviousUserId( $id )**: must store the parameter $id in session, for example:
```php
$_SESSION[ 'PreviousUserId' ] = $id;
```
* **getPreviousUserId()**: return the value stored in the previous method, for example:
```php
return $_SESSION[ 'PreviousUserId' ];
```
* **setPreviousSnapshot( $snapshot )**: must store the parameter $snapshot in session, for example:
```php
$_SESSION[ 'PreviousSnapshot' ] = $snapshot;
```
* **getPreviousSnapshot()**: return the value stored in the previous method, for example:
```php
return $_SESSION[ 'PreviousSnapshot' ];
```

## new composer command
This plugin define a new composer comand: **create-snapshot**. You must run this command each time you update you code to create a new code snapshot. 
```shell
php composer.phar create-snapshot
```
## choose the snapshot
You must edit the file **./snapshot/snapshot.json** and choose the :

```javascript
{
    "snapshot":{
        "1": [ "default", "cli" ],
        "2":[ "122", "123" ]
    }
}
```
## warning
The SessionHandler class (and all the classes used in its __construct() method) allways loads from default snapshot because previously we don't know the user ID.

### That's all
