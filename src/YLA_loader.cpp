/*
 * This file is part of the YggdrasilCore Project. See AUTHORS file for Copyright information
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/>.
 */

// From SC
void AddSC_YLA();

// Add all
void Addmod_ylaScripts()
{
    AddSC_YLA();
}

// Compat: folder still checked out as modules/mod-ale until repo rename
void Addmod_aleScripts()
{
    Addmod_ylaScripts();
}

// Load order: register YLA's scripts before other modules (e.g. mod-shadows),
// so player-chat hooks such as PLAYER_EVENT_ON_WHISPER run first
int Addmod_ylaLoadOrder()
{
    return 1;
}

// Compat: folder still checked out as modules/mod-ale until repo rename
int Addmod_aleLoadOrder()
{
    return Addmod_ylaLoadOrder();
}
