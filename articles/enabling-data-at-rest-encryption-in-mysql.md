# Enabling data at rest encryption in Mysql
* url: http://datachild.net/data/enabling-data-at-rest-encryption-in-mysql
* category: data
* published: 2023-06-16
* tags: mysql, security
* description: Data-at-rest encryption is important to ensure that data is secured from direct access to original database files. Let's see how to enable and use data-at-rest encryption in Mysql, which is supported for InnoDB storage engine.

In an era where data breaches and cyber threats loom large, safeguarding sensitive information has become a top priority for businesses of all sizes. As organizations increasingly rely on databases to store and manage critical data, implementing robust security measures has become imperative. One powerful weapon in the data protection arsenal is the data-at-rest encryption, a technique that shields data when it's stored on disk.

Let's see how data-at-rest encryption can be delivered in the context of MySQL.


## Encryption prerequisites

The idea behind data-at-rest encryption is to make sure, that data on the disk can't be accessed without the encryption key. Mysql introduces the concept of the master encryption key, which is used to encrypt data. When this key is unavailable there's no way to access the data.

First of all, make sure you have the latest possible Mysql version installed:

```
mysql -e "select version()"
+-------------------------+
| version()               |
+-------------------------+
| 8.0.33-0ubuntu0.23.04.2 |
+-------------------------+
```
* `mysql -e` - runs Mysql query right in the console,
* `select version()` - this will show the current Mysql version.

Mysql 5.x branch also supports the data-at-rest encryption.

Another requirement is to have all tables use the InnoDB engine since other engines are not supported for encryption. If you have MyISAM tables (and that's not a well-considered choice), you can easily convert to InnoDB:
```
ALTER TABLE table_name ENGINE=InnoDB;
```
* `ENGINE=InnoDB` - this will rebuild the table using the InnoDB engine, but that can take quite a time for big tables and result in using more disk space.



## Enabling encryption

First of all, we have to enable the [keyring_file plugin](https://dev.mysql.com/doc/refman/8.0/en/keyring-file-plugin.html). 

```
[mysqld]
early-plugin-load=keyring_file.so
```
* `early-plugin-load` - this will load the given plugin before initializing storage engines,
* `keyring_file.so` - this plugin is already installed with Mysql, we just need to load it.

After the server restart we can see this plugin is loaded:
```
mysql> show plugins;
+---------------------------------+----------+--------------------+-----------------+---------+
| Name                            | Status   | Type               | Library         | License |
+---------------------------------+----------+--------------------+-----------------+---------+
| keyring_file                    | ACTIVE   | KEYRING            | keyring_file.so | GPL     |
...
```



## Encrypting tables

Now if we want to create the encrypted table, we just add `ENCRYPTION='Y'` to the DDL statement:
```
CREATE TABLE new_table (
  ...
) ENGINE=InnoDB ENCRYPTION='Y'
```
`ENCRYPTION='Y'` - this enables encryption for the new table.

Existing tables can be encrypted using the `ALTER` statement:

```
ALTER TABLE old_table ENCRYPTION = 'y';
```

This can take some time for bigger tables since Mysql will have to encrypt and save the entire table data.

That's it, now your table data is encrypted and safe.



## Checking encryption

We can find out if tables are encrypted or not using the `INFORMATION_SCHEMA` database:

```
SELECT NAME, ENCRYPTION FROM INFORMATION_SCHEMA.INNODB_TABLESPACES
```
```output
+-------------------------------------------------+------------+
| NAME                                            | ENCRYPTION |
+-------------------------------------------------+------------+
| mysql                                           | **N**          |
| db/users                                        | **Y**          |
...
```
* `NAME` - the name of the table space (in simple words - database and table name),
* `ENCRYPTION` - will show `Y` for encrypted and `N` for not encrypted tables.



## Securing master key

The master key is used for encrypting and decrypting table data on disk. It is generated automatically upon first usage and is stored in a password-protected file on the disk:
```
show variables like 'keyring_file_data';
```
```output
+-------------------+--------------------------------+
| Variable_name     | Value                          |
+-------------------+--------------------------------+
| keyring_file_data | /var/lib/mysql-keyring/keyring |
+-------------------+--------------------------------+
```
* `/var/lib/mysql-keyring/keyring` - path to the file with the master key.

A good practice is to backup this file to protected external storage.

Another thing to do periodically - is to rotate the master key with the following query:
```
ALTER INSTANCE ROTATE INNODB MASTER KEY;
```
* `ROTATE INNODB MASTER KEY` - this will generate a new master key and re-encrypt all data automatically.



## Data-in-transit encryption
Another part of securing our data is to use data-in-transit encryption as well. This means encrypting what's being sent and received by the Mysql server. SSL is a popular way to achieve that and is well supported by Mysql. Check the Mysql guide to set up [Mysql SSL connection](https://dev.mysql.com/doc/refman/8.0/en/using-encrypted-connections.html).


## Further reading
- [InnoDB Data-at-Rest Encryption](https://dev.mysql.com/doc/refman/8.0/en/innodb-data-encryption.html)
- [Using the keyring_file File-Based Keyring Plugin](https://dev.mysql.com/doc/refman/8.0/en/keyring-file-plugin.html)