package com.networkcards.companion

import android.content.Context

class CompanionPreferences(context: Context) {
    private val prefs = context.getSharedPreferences("companion", Context.MODE_PRIVATE)

    fun baseUrl(): String = prefs.getString("base_url", "") ?: ""
    fun setBaseUrl(value: String) = prefs.edit().putString("base_url", value.trim().trimEnd('/')).apply()
    fun token(): String? = prefs.getString("token", null)
    fun setToken(value: String) = prefs.edit().putString("token", value).apply()
    fun clearToken() = prefs.edit().remove("token").apply()
    fun deviceLockEnabled(): Boolean = prefs.getBoolean("device_lock", false)
    fun setDeviceLockEnabled(value: Boolean) = prefs.edit().putBoolean("device_lock", value).apply()
    fun defaultSimSlot(): Int = prefs.getInt("default_sim_slot", 0).coerceAtLeast(0)
    fun setDefaultSimSlot(value: Int) = prefs.edit().putInt("default_sim_slot", value.coerceAtLeast(0)).apply()
}
