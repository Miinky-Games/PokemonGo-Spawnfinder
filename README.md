# PokemonGo-Spawnfinder

## Overview
This is an extension for the popular [PokemonGo-Map](https://github.com/AHAAAAAAA/PokemonGo-Map).

It allows evaluating the data that has been crawled with the PokemonGo-Map if the data is stored in an MySQL database, which is possible with newer versions of the PokemonGo-Map. *For instructions how to achieve this visit the [Wiki](https://github.com/AHAAAAAAA/PokemonGo-Map/wiki) of the PokemonGo-Map*

More exactly is this a single .php file which lets you search for spawnpoints of specific pokemon along with some extra informations about the spawnpoints.
E.g. exact spawntimes and frequency of specific pokemon spawns.

## How to use

- Download the [master branch](https://github.com/polygamma/PokemonGo-Spawnfinder/archive/master.zip) for a stable release or the [develop branch](https://github.com/polygamma/PokemonGo-Spawnfinder/archive/develop.zip) if you want to use the newest features.

- Edit the lines at the top of **index.php** which are meant for being edited.

- Place the **index.php** on a webserver which supports PHP.

- Open the **index.php** in your favorite webbrowser.

- Enter the **ID** of the pokemon you are looking for in the input field at the top and press the button.

## Functionality

By now you will see all spawnpoints at which the pokemon you are looking for has been seen.

Clicking on the markers yields additional, self-explaining information.

Yellow markers signalize that there is not enough data to predict if the specific pokemon spawns more often than usual at the spawnpoint.

Green markers signalize that there is enough data and the specific pokemon spawns quite often at that spawnpoint.

Red markers signalize that there is enough data and the specific pokemon spawns **not** quite often at that spawnpoint.

## Contributions

Please submit all pull requests to the [develop branch](https://github.com/polygamma/PokemonGo-Spawnfinder/archive/develop.zip).