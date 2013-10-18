# Autom8-Thold

Automate threshold creation for data sources

## Purpose

This plugin allows to create rules similar to Autom8 Graph - and Tree Rules to specify which thresholds need to be added to data sources.

## Features

* Define Rules based on existing Data Queries
* Preview of matching Hosts and Data Sources

## Prerequisites

This plugin requires the Autom8 plugin to be installed and activated.
The Thold plugin, of which the versions 0.4.9 and 0.5.0 are compatible, needs to be installed too.
This plugin is tested with Cacti 0.8.8a and PIA 3.1 but it should also work on older versions. 
This plugin contains patches for Autom8 v0.35, older versions of the plugin will need different patches and might not work with this plugin.

## Installation

1. Untar contents to the Cacti plugins directory
2. Apply following [patches](#patches)
3. Install and Activate from Cacti UI

### Patches

In order to get this plugin running, applying patches to the plugin Autom8 is required.

Run following commands from plugins directory:

```shell
patch --dry-run -N -d autom8/ -i autom8thold/autom8_v035.patch
```

If everything looks ok, you can omit the `--dry-run` option and run the commands again to actually do the patches. 

**Note:** The patch autom8_v035.patch might warn you about reverting patches if you have done this patch already for the plugin [Autom8-Reportit](https://github.com/Super-Visions/cacti-plugin-autom8reportit/). 
In this case, you can ignore this patch as it is already done.
