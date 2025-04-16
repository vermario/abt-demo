# Access by Taxonomy

This module allows to limit access control to nodes based on the taxonomy terms that they are associated with.

The access control is configured on a per-term basis. The module adds two fields to each taxonomy terms, one for roles and one for users. The fields are used to specify which roles and/or users will get access to all nodes associated with the term.

This module is compatible with content translation.

Note: This module only deals with the `view` operation for nodes, `update` and `delete` operations are not affected.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/access_by_taxonomy).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/access_by_taxonomy).


## Requirements

This module requires no modules outside of Drupal core.


## Installation

Install as you would normally install a contributed Drupal module. For further information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

By default only administrators and users with the `administer taxonomy` permission can configure the access control.
If you want to allow other roles to configure the access control, grant the `access by taxonomy administer access for terms in [TYPE_ID]` permission to the roles that should be able to do so. This permission is specific for each taxonomy vocabulary.

## Configuration

1. Enable the module at Administration > Extend.
1. If your site already has taxonomy dictionaries set up, two new fields will be added to the dictionaries.
1. If you don't have any dictionaries set up, create a new dictionary (the fields will be added automatically).
1. Create new terms in your dictionary, specifying the roles and / or users that will have access to the nodes associated with the term. The fields are not mandatory, and leaving them empty will not influence the access control for the nodes they will be associated to.
1. Associate the terms with the nodes you want to restrict access to.
1. If you want users of a specific role to always be granted access to view nodes of a given content type, grant the permission `View any %type_name content` to the role, provided by this module.


## Maintainers

- Federico Prato - [joey-santiago](https://www.drupal.org/u/joey-santiago)
- Mario Vercellotti - [vermario](https://www.drupal.org/u/vermario)
- Hannes Kirsman - [hkirsman](https://www.drupal.org/u/hkirsman)
- Raimonds Kalniņš - [maijs](https://www.drupal.org/u/maijs)
