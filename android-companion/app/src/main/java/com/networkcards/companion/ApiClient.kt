package com.networkcards.companion

import android.content.Context
import org.json.JSONObject
import java.net.HttpURLConnection
import java.net.URL

data class ApiResponse(val status: Int, val body: JSONObject)

object ApiClient {
    fun post(context: Context, path: String, payload: JSONObject, authenticated: Boolean = true): ApiResponse {
        return request(context, "POST", path, payload, authenticated)
    }

    fun get(context: Context, path: String): ApiResponse {
        return request(context, "GET", path, null, true)
    }

    private fun request(
        context: Context,
        method: String,
        path: String,
        payload: JSONObject?,
        authenticated: Boolean
    ): ApiResponse {
        val prefs = CompanionPreferences(context)
        val baseUrl = prefs.baseUrl().trimEnd('/')
        require(baseUrl.startsWith("https://")) { "يجب استخدام رابط HTTPS آمن." }

        val connection = URL("$baseUrl/$path").openConnection() as HttpURLConnection
        connection.requestMethod = method
        connection.connectTimeout = 10000
        connection.readTimeout = 15000
        connection.setRequestProperty("Accept", "application/json")
        if (authenticated) {
            val token = prefs.token() ?: throw IllegalStateException("يجب تسجيل الدخول أولاً.")
            connection.setRequestProperty("Authorization", "Bearer $token")
        }

        if (payload != null) {
            connection.doOutput = true
            connection.setRequestProperty("Content-Type", "application/json")
            connection.outputStream.use { it.write(payload.toString().toByteArray(Charsets.UTF_8)) }
        }

        val status = connection.responseCode
        val stream = if (status in 200..399) connection.inputStream else connection.errorStream
        val responseText = stream?.bufferedReader()?.use { it.readText() }.orEmpty()
        connection.disconnect()
        return ApiResponse(status, if (responseText.isBlank()) JSONObject() else JSONObject(responseText))
    }
}
