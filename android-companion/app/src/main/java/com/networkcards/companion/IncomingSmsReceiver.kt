package com.networkcards.companion

import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.provider.Telephony
import android.provider.Settings
import org.json.JSONObject
import java.time.Instant

class IncomingSmsReceiver : BroadcastReceiver() {
    override fun onReceive(context: Context, intent: Intent) {
        if (intent.action != Telephony.Sms.Intents.SMS_RECEIVED_ACTION) return
        val messages = Telephony.Sms.Intents.getMessagesFromIntent(intent)
        if (messages.isEmpty()) return

        val sender = messages.first().originatingAddress ?: return
        val body = messages.joinToString("") { it.messageBody.orEmpty() }
        val receivedAt = messages.maxOf { it.timestampMillis }
        val pendingResult = goAsync()

        Thread {
            try {
                val payload = JSONObject()
                    .put("sender", sender)
                    .put("raw_message", body)
                    .put("received_at", Instant.ofEpochMilli(receivedAt).toString())
                    .put("source_device", Settings.Secure.getString(context.contentResolver, Settings.Secure.ANDROID_ID))
                val response = ApiClient.post(context, "companion/bank-sms", payload)
                if (response.status !in 200..299) {
                    android.util.Log.e("CompanionSMS", "Bank notification forwarding failed with HTTP ${response.status}")
                }
            } catch (error: Exception) {
                android.util.Log.e("CompanionSMS", "Unable to forward incoming bank notification", error)
            } finally {
                pendingResult.finish()
            }
        }.start()
    }
}
