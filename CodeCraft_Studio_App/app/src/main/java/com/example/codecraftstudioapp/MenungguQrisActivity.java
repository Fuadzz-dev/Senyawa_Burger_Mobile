package com.example.codecraftstudioapp;

import android.content.Intent;
import android.os.Bundle;
import android.os.Handler;
import android.view.View;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.bumptech.glide.Glide;
import com.example.codecraftstudioapp.models.StatusResponse;
import com.example.codecraftstudioapp.network.ApiClient;
import com.example.codecraftstudioapp.network.ApiService;

import java.io.UnsupportedEncodingException;
import java.net.URLEncoder;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class MenungguQrisActivity extends AppCompatActivity {

    private ImageView imgQr;
    private TextView tvTitleStatus, tvDesc, tvWaitText;
    private Button btnMenu;
    private ProgressBar progressBarStatus;

    private String qrString;
    private String reference;
    private Handler handler;
    private Runnable pollingRunnable;
    private ApiService apiService;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_menunggu_qris);

        imgQr = findViewById(R.id.imgQr);
        tvTitleStatus = findViewById(R.id.tvTitleStatus);
        tvDesc = findViewById(R.id.tvDesc);
        tvWaitText = findViewById(R.id.tvWaitText);
        btnMenu = findViewById(R.id.btnMenu);
        progressBarStatus = findViewById(R.id.progressBarStatus);

        apiService = ApiClient.getClient().create(ApiService.class);

        qrString = getIntent().getStringExtra("QR_STRING");
        reference = getIntent().getStringExtra("REFERENCE");

        if (qrString != null && !qrString.isEmpty()) {
            displayQR(qrString);
        }

        if (reference != null && !reference.isEmpty()) {
            startPolling();
        }

        btnMenu.setOnClickListener(v -> {
            Intent intent = new Intent(MenungguQrisActivity.this, MainActivity.class);
            startActivity(intent);
            finishAffinity();
        });
    }

    private void displayQR(String data) {
        try {
            String encodedData = URLEncoder.encode(data, "UTF-8");
            String qrUrl = "https://quickchart.io/qr?size=500&text=" + encodedData;
            
            Glide.with(this)
                    .load(qrUrl)
                    .placeholder(R.mipmap.ic_launcher)
                    .into(imgQr);
        } catch (UnsupportedEncodingException e) {
            e.printStackTrace();
        }
    }

    private void startPolling() {
        handler = new Handler();
        pollingRunnable = new Runnable() {
            @Override
            public void run() {
                checkStatus();
                handler.postDelayed(this, 5000); // Poll every 5 seconds
            }
        };
        handler.post(pollingRunnable);
    }

    private void checkStatus() {
        apiService.checkQrisStatus(reference).enqueue(new Callback<StatusResponse>() {
            @Override
            public void onResponse(Call<StatusResponse> call, Response<StatusResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    if (response.body().isSuccess()) {
                        String statusCode = response.body().getStatusCode();
                        if ("00".equals(statusCode)) {
                            tvTitleStatus.setText("Pembayaran Berhasil!");
                            tvDesc.setText("Terima kasih, pembayaran QRIS Anda telah diterima.");
                            
                            progressBarStatus.setVisibility(View.GONE);
                            tvWaitText.setVisibility(View.GONE);
                            btnMenu.setVisibility(View.VISIBLE);
                            
                            if (handler != null) handler.removeCallbacks(pollingRunnable);
                        } else if ("01".equals(statusCode)) {
                            tvTitleStatus.setText("Pembayaran Gagal/Expired");
                            tvDesc.setText("Sesi QRIS Anda telah berakhir.");
                            
                            progressBarStatus.setVisibility(View.GONE);
                            tvWaitText.setVisibility(View.GONE);
                            btnMenu.setVisibility(View.VISIBLE);

                            if (handler != null) handler.removeCallbacks(pollingRunnable);
                        }
                    }
                }
            }

            @Override
            public void onFailure(Call<StatusResponse> call, Throwable t) {}
        });
    }

    @Override
    protected void onDestroy() {
        super.onDestroy();
        if (handler != null && pollingRunnable != null) {
            handler.removeCallbacks(pollingRunnable);
        }
    }
}
