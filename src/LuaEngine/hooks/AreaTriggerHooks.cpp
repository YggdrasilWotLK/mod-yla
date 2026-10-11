/*
 * Copyright (C) 2010 - 2025 Eluna Lua Engine <https://elunaluaengine.github.io/>
 * This program is free software licensed under GPL version 3
 * Please see the included DOCS/LICENSE.md for more information
 */

#include "Hooks.h"
#include "HookHelpers.h"
#include "LuaEngine.h"
#include "BindingMap.h"
#include "YLATemplate.h"

using namespace Hooks;

#define START_HOOK(EVENT, ENTRY)\
    if (!YLAConfig::GetInstance().IsYLAEnabled())\
        return;\
    LOCK_YLA_STATE;\
    if (!YLA::IsInitialized())\
        return;\
    auto key = EntryKey<AreaTriggerEvents>(EVENT, ENTRY);\
    if ((!AreaTriggerEventBindings || !AreaTriggerEventBindings->HasBindingsFor(key)))\
        return;

void YLA::OnAreaTriggerEntry(Player* player, uint32 triggerId)
{
    START_HOOK(AREATRIGGER_EVENT_ON_TRIGGER, triggerId);
    Push(player);
    Push(triggerId);
    CallAllFunctions(AreaTriggerEventBindings, key);
}
