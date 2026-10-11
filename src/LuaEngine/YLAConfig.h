#ifndef YLA_CONFIG_HPP
#define YLA_CONFIG_HPP

#include "ConfigValueCache.h"
#include <unordered_set>

enum class YLAConfigValues : uint32
{
    // Boolean
    ENABLED = 0,
    TRACEBACK_ENABLED,
    AUTORELOAD_ENABLED,
    BYTECODE_CACHE_ENABLED,
    COMPATIBILITY_MODE,

    // String
    SCRIPT_PATH,
    REQUIRE_PATH,
    REQUIRE_CPATH,
    ONLY_ON_MAPS,

    // Number
    AUTORELOAD_INTERVAL,

    CONFIG_VALUE_COUNT
};

class YLAConfig final : public ConfigValueCache<YLAConfigValues>
{
    public:
        static YLAConfig& GetInstance();

        void Initialize(bool reload = false);

        bool IsYLAEnabled() const { return GetConfigValue<bool>(YLAConfigValues::ENABLED); }
        bool IsTraceBackEnabled() const { return GetConfigValue<bool>(YLAConfigValues::TRACEBACK_ENABLED); }
        bool IsAutoReloadEnabled() const { return GetConfigValue<bool>(YLAConfigValues::AUTORELOAD_ENABLED); }
        bool IsByteCodeCacheEnabled() const { return GetConfigValue<bool>(YLAConfigValues::BYTECODE_CACHE_ENABLED); }
        bool IsCompatibilityModeEnabled() const { return GetConfigValue<bool>(YLAConfigValues::COMPATIBILITY_MODE); }

        std::string_view GetScriptPath() const { return GetConfigValue(YLAConfigValues::SCRIPT_PATH); }
        std::string_view GetRequirePath() const { return GetConfigValue(YLAConfigValues::REQUIRE_PATH); }
        std::string_view GetRequireCPath() const { return GetConfigValue(YLAConfigValues::REQUIRE_CPATH); }

        uint32 GetAutoReloadInterval() const { return GetConfigValue<uint32>(YLAConfigValues::AUTORELOAD_INTERVAL); }

        bool ShouldMapLoadYLA(uint32 mapId) const;
        bool ShouldMapLoadYLAByFolderName(const std::string& folderName, uint32 mapId) const;

    protected:
        void BuildConfigCache() override;

    private:
        YLAConfig();
        ~YLAConfig() = default;
        YLAConfig(const YLAConfig&) = delete;
        YLAConfig& operator=(const YLAConfig&) = delete;

        void TokenizeAllowedMaps();

        std::unordered_set<uint32> m_allowedMaps;
};

#endif // YLA_CONFIG_H