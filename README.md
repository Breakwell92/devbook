# DevBook Documentation


## Setup
Requires Homestead/Vagrant

- Run `composer install`
- Run `vagrant up`
- Run `vagrant ssh`
- Run `mysql -u homestead -p`
- Enter password (secret)
- Run `CREATE DATABASE devbook`
- Run `exit`
- Run `php artisan migrate`


## Create a profile
**Method:** POST

**URL:** devbook.local/api/users

Available fields:  (*required)
- `email *
- `first_name` *
- `surname`
- `dob` (minimum age 18) *
- `national_insurance_no`
- `profile_image` (URL)
- `full_address`
- `bio`

You will receive a token when your profile is created. Keep a note of this.

## Show a profile
**Method:** GET

**URLs:**

- devbook.local/api/users/me - Returns your profile
- devbook.local/api/users/{id} - Returns profile with matching ID
- devbook.local/api/users/{email} - Returns profile with matching email

Requires token adding to request header.

If the profile being returned is not yours, sensitive information will be omitted from the returned data.

## Update your profile
**Method:** PATCH

**URL:** devbook.local/api/users/me

Requires token adding to request header.

You can update any field except for email, dob and national_insurance_no. If you attempt to edit these fields, the request will fail.

## Delete your profile
**Method:** DELETE

**URL:** devbook.local/api/users/me

Requires token adding to request header.

Will instantly force delete your profile (not soft delete)

## Search profiles based on keywords in bio
**Method:** GET

**URL:** devbook.local/api/users?search=add,search,terms,comma,separated

Requires token adding to request header.

Will return profiles where the users bio contains at least 1 of the search terms



