-- Hey there! 👋 Welcome to the fancy manifest file where we tell FiveM what's what

fx_version 'cerulean'  -- Living on the edge with the latest and greatest
game 'gta5'           -- In case you couldn't guess which game this is for 😉

author 'SubhaM'
description 'A fancy-pants guidebook that makes reading actually fun!'
version '1.0.0'     

-- The essentials
client_scripts {
    'client/main.lua',   -- Where the magic happens
    'config.lua'         -- Configuration settings
}

server_scripts {
    'server/server.lua'  -- Server-side sorcery
}

-- Web UI files (include all required assets)
files {
    'ui/guidebook.html',      -- Regular interface
    'ui/guidebook-admin.html', -- Admin interface
    'ui/mockdata.js',         -- Development data
    'ui/mockdata.json',       -- Live data for FiveM
    'ui/*.css',              -- Any CSS files
    'ui/*.js'                -- Any JS files
}

-- Only one ui_page is allowed, we use guidebook.html as main
ui_page 'ui/guidebook.html'

-- Dependency declarations
dependencies {
    'qb-core' -- If you're using QB-Core framework
}