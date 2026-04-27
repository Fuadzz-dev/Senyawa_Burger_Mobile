package com.example.codecraftstudioapp;

import androidx.appcompat.app.AppCompatActivity;
import android.os.Bundle;
import android.webkit.WebSettings;
import android.webkit.WebView;
import android.webkit.WebViewClient;

public class MainActivity extends AppCompatActivity {

    private WebView webView;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_menu);

        webView = findViewById(R.id.webView);

        // Konfigurasi WebSettings
        WebSettings webSettings = webView.getSettings();
        webSettings.setJavaScriptEnabled(true); // Wajib agar JS di web jalan
        webSettings.setDomStorageEnabled(true); // Wajib agar localStorage jalan (untuk keranjang)

        // Supaya tidak membuka browser eksternal ketika diklik link
        webView.setWebViewClient(new WebViewClient());

        // Load URL website Laravel (artisan serve port 8000)
        // 10.0.2.2 digunakan untuk mengakses localhost dari Android Emulator
        webView.loadUrl("http://10.0.2.2:8000/");
    }

    // Mengatasi tombol back di Android agar kembali ke history WebView bukan menutup aplikasi
    @Override
    public void onBackPressed() {
        if (webView.canGoBack()) {
            webView.goBack();
        } else {
            super.onBackPressed();
        }
    }
}