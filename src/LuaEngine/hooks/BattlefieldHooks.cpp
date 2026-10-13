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

#define START_HOOK_BATTLEFIELD(EVENT)\
    if (!YLAConfig::GetInstance().IsYLAEnabled())\
        return;\
    LOCK_YLA;\
    /* WORLD dispatch runs Lua on this state: hold its lock too. */\
    YLA::Guard __yla_world_state_guard(this->GetStateLock());\
    if (!YLA::IsInitialized())\
        return;\
    auto key = EventKey<BattlefieldEvents>(EVENT);\
    if ((!BattlefieldEventBindings || !BattlefieldEventBindings->HasBindingsFor(key)))\
        return;

void YLA::OnBattlefieldWarStart(uint32 battleId)
{
    START_HOOK_BATTLEFIELD(BFIELD_EVENT_ON_WAR_START);
    Push(battleId);
    CallAllFunctions(BattlefieldEventBindings, key);
}

void YLA::OnBattlefieldWarEnd(uint32 battleId, bool endByTimer)
{
    START_HOOK_BATTLEFIELD(BFIELD_EVENT_ON_WAR_END);
    Push(battleId);
    Push(endByTimer);
    CallAllFunctions(BattlefieldEventBindings, key);
}
