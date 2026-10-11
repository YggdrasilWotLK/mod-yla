/*
 * Copyright (C) 2010 - 2026 YLA Lua Engine <https://github.com/YggdrasilWotLK/mod-yla>
 * This program is free software licensed under GPL version 3.
 * Please see the included LICENSE file for more information.
 *
 * ShadowPlayer: Lua-visible sub-class of Player for mod-shadows (bot) support.
 *
 * Design notes:
 * - Layout-identical to Player (no data members, no new virtuals). It is never
 *   instantiated on its own; existing Player* bot pointers are reinterpreted as
 *   ShadowPlayer* purely to give Lua a distinct "Shadow" metatable while
 *   inheriting every Player/Object/WorldObject/Unit binding.
 * - Because ShadowPlayer derives from Player, a ShadowPlayer* implicitly
 *   converts to Player* whenever core or YLA APIs need the base object.
 * - The type is always registered (even without mod-shadows) so Lua scripts can
 *   probe for bot support at runtime; bot-specific methods return safe
 *   fallbacks (nil/false/empty) when MOD_SHADOWS is not compiled in.
 */

#ifndef _SHADOW_PLAYER_H
#define _SHADOW_PLAYER_H

#include "ObjectAccessor.h"
#include "Player.h"
#include "YlaAlive.h"

class ShadowPlayer : public Player
{
public:
    // Never instantiated directly; only used as a Lua type tag.
    // Layout-identical to Player: no members, no virtuals.
    static ShadowPlayer* FromPlayer(Player* player)
    {
        return reinterpret_cast<ShadowPlayer*>(player);
    }

    static Player* ToPlayer(ShadowPlayer* shadow)
    {
        return static_cast<Player*>(shadow);
    }

    static Player const* ToPlayer(ShadowPlayer const* shadow)
    {
        return static_cast<Player const*>(shadow);
    }

    // Liveness helper: true when the underlying Player still exists in the
    // world (mirrors YLATemplate<Player> GUID resolution for the Shadow type,
    // whose generic YLAObject does not capture a player GUID).
    static bool IsLive(ShadowPlayer* shadow)
    {
        if (!shadow)
            return false;
        Player* player = ToPlayer(shadow);
        if (!player)
            return false;
        ObjectGuid guid = player->GetGUID();
        if (guid.IsEmpty())
            return false;
        if (ObjectAccessor::FindPlayer(guid) == player)
            return true;
        YlaAlive::Guard guard{ YlaAlive::Mutex() };
        return YlaAlive::MatchesLocked(guid, static_cast<WorldObject*>(player));
    }

private:
    ShadowPlayer() = delete;
    ShadowPlayer(ShadowPlayer const&) = delete;
    ShadowPlayer& operator=(ShadowPlayer const&) = delete;
    ~ShadowPlayer() = delete;
};

#endif // _SHADOW_PLAYER_H
