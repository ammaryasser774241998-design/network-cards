package com.networkcards.companion

import android.Manifest
import android.content.Context
import android.content.pm.PackageManager
import android.os.Build
import androidx.core.content.ContextCompat

object CompanionPermissions {
    fun requiredRuntimePermissions(sdkInt: Int = Build.VERSION.SDK_INT): List<String> = buildList {
        add(Manifest.permission.RECEIVE_SMS)
        if (sdkInt >= 33) add(Manifest.permission.POST_NOTIFICATIONS)
    }

    fun missing(context: Context): List<String> = requiredRuntimePermissions().filter {
        ContextCompat.checkSelfPermission(context, it) != PackageManager.PERMISSION_GRANTED
    }
}