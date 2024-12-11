print('Server script loaded')

-- Debug helper
local function Debug(msg)
    print('^5[Guidebook Server]^7 ' .. msg)
end

-- Handle data requests
RegisterNetEvent('guidebook:getData')
AddEventHandler('guidebook:getData', function()
    local source = source
    local resourcePath = GetResourcePath(GetCurrentResourceName())
    local file = io.open(resourcePath .. '/ui/mockdata.json', 'r')
    
    if file then
        local content = file:read('*all')
        file:close()
        TriggerClientEvent('guidebook:receiveData', source, json.decode(content))
        Debug('Data sent to client')
    else
        Debug('Failed to load mockdata.json')
    end
end)

-- Data saving functionality
RegisterNetEvent('guidebook:saveData')
AddEventHandler('guidebook:saveData', function(data)
    local resourcePath = GetResourcePath(GetCurrentResourceName())
    local file = io.open(resourcePath .. '/data.json', 'w')
    
    if file then
        file:write(json.encode(data))
        file:close()
        Debug('Data saved successfully')
    else
        Debug('Failed to save data')
    end
end)

-- Server ready check
RegisterNetEvent('guidebook:checkServer')
AddEventHandler('guidebook:checkServer', function()
    local source = source
    TriggerClientEvent('guidebook:serverStatus', source, true)
    Debug('Server status check - Ready')
end)