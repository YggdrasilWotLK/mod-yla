#include "YLAConfig.h"
#include "YLAUtility.h"
#include <sstream>
#include <algorithm>
#include <cctype>

YLAConfig& YLAConfig::GetInstance()
{
    static YLAConfig instance;
    return instance;
}

YLAConfig::YLAConfig() : ConfigValueCache<YLAConfigValues>(YLAConfigValues::CONFIG_VALUE_COUNT)
{
}

void YLAConfig::Initialize(bool reload)
{
    ConfigValueCache<YLAConfigValues>::Initialize(reload);
    TokenizeAllowedMaps();
}

void YLAConfig::BuildConfigCache()
{
    SetConfigValue<bool>(YLAConfigValues::ENABLED,                    "YLA.Enabled",            "false");
    SetConfigValue<bool>(YLAConfigValues::TRACEBACK_ENABLED,          "YLA.TraceBack",          "false");
    SetConfigValue<bool>(YLAConfigValues::AUTORELOAD_ENABLED,         "YLA.AutoReload",         "false");
    SetConfigValue<bool>(YLAConfigValues::BYTECODE_CACHE_ENABLED,     "YLA.BytecodeCache",      "false");
    SetConfigValue<bool>(YLAConfigValues::COMPATIBILITY_MODE,         "YLA.CompatibilityMode",  "true");

    SetConfigValue<std::string>(YLAConfigValues::SCRIPT_PATH,         "YLA.ScriptPath",         "lua_scripts");
    SetConfigValue<std::string>(YLAConfigValues::REQUIRE_PATH,        "YLA.RequirePaths",       "");
    SetConfigValue<std::string>(YLAConfigValues::REQUIRE_CPATH,       "YLA.RequireCPaths",      "");
    SetConfigValue<std::string>(YLAConfigValues::ONLY_ON_MAPS,        "YLA.OnlyOnMaps",         "");

    SetConfigValue<uint32>(YLAConfigValues::AUTORELOAD_INTERVAL,      "YLA.AutoReloadInterval", 1);
}

bool YLAConfig::ShouldMapLoadYLA(uint32 mapId) const
{
    if (m_allowedMaps.empty())
        return true;
    return m_allowedMaps.find(mapId) != m_allowedMaps.end();
}

bool YLAConfig::ShouldMapLoadYLAByFolderName(const std::string& folderName, uint32 mapId) const
{
    std::string digits;
    for (char c : folderName)
    {
        if (std::isdigit(static_cast<unsigned char>(c)))
            digits += c;
        else
            break;
    }

    if (digits.empty())
        return true;

    try
    {
        uint32 folderMapId = std::stoul(digits);
        return folderMapId == mapId;
    }
    catch (std::exception&)
    {
        return true;
    }
}

void YLAConfig::TokenizeAllowedMaps()
{
    m_allowedMaps.clear();

    std::istringstream maps(static_cast<std::string>(GetConfigValue(YLAConfigValues::ONLY_ON_MAPS)));
    std::string mapIdStr;
    while (std::getline(maps, mapIdStr, ','))
    {
        mapIdStr.erase(std::remove_if(mapIdStr.begin(), mapIdStr.end(), [](char c) {
            return std::isspace(static_cast<unsigned char>(c));
        }), mapIdStr.end());

        if (mapIdStr.empty())
            continue;

        try
        {
            uint32 mapId = std::stoul(mapIdStr);
            m_allowedMaps.emplace(mapId);
        }
        catch (std::exception&)
        {
            YLA_LOG_ERROR("[YLAConfig]: Invalid map ID in YLA.OnlyOnMaps: '{}'", mapIdStr);
        }
    }
}
