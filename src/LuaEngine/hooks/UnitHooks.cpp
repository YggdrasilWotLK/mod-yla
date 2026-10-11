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

#define START_HOOK(EVENT)\
    if (!YLAConfig::GetInstance().IsYLAEnabled())\
        return;\
    LOCK_YLA_STATE;\
    if (!YLA::IsInitialized())\
        return;\
    auto key = EventKey<UnitEvents>(EVENT);\
    if ((!UnitEventBindings || !UnitEventBindings->HasBindingsFor(key)))\
        return;

void YLA::OnUnitDisplayIdChange(Unit* unit, uint32 displayId)
{
    START_HOOK(UNIT_EVENT_ON_DISPLAYID_CHANGE);
    Push(unit);
    Push(displayId);
    CallAllFunctions(UnitEventBindings, key);
}

void YLA::OnUnitShapeshiftForm(Unit* unit, uint8 form)
{
    START_HOOK(UNIT_EVENT_ON_SHAPESHIFT_FORM);
    Push(unit);
    Push(form);
    CallAllFunctions(UnitEventBindings, key);
}
