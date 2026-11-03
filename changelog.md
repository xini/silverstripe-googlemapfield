# Changelog

All notable changes to this project will be documented in this file.

This project adheres to [Semantic Versioning](http://semver.org/).

## [3.1.0]

* add MutationObserver to init dynamically loaded fields, only load map dependencies when needed (fixes #6)
* remove obsolete scrutinizer config

## [3.0.1]

* fix search field name
* fix reloading of map after saving
* code cleanup

## [3.0.0]

* Upgrade for Silverstripe 6 (thanks @lerni)
* Move API key config from individual fields to global field config
* Load field requirements from LeftAndMain extension intsead of field to fix map display after saving
