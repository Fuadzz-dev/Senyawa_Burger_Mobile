package com.example.codecraftstudioapp;

import android.content.Intent;
import android.os.Bundle;
import android.os.Handler;
import android.view.View;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.codecraftstudioapp.models.PesananResponse;
import com.example.codecraftstudioapp.models.StatusResponse;
import com.example.codecraftstudioapp.network.ApiClient;
import com.example.codecraftstudioapp.network.ApiService;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class MenungguKasirActivity extends AppCompatActivity {

    private TextView tvPesananId, tvPesananTotal, tvTitleStatus, tvDesc;
    private Button btnMenu;
    private int idPesanan;
    private Handler handler;
    private Runnable pollingRunnable;
    private ApiService apiService;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_menunggu_kasir);

        tvPesananId = findViewById(R.id.tvPesananId);
        tvPesananTotal = findViewById(R.id.tvPesananTotal);
        tvTitleStatus = findViewById(R.id.tvTitleStatus);
        tvDesc = findViewById(R.id.tvDesc);
        btnMenu = findViewById(R.id.btnMenu);

        apiService = ApiClient.getClient().create(ApiService.class);
        idPesanan = getIntent().getIntExtra("ID_PESANAN", -1);

        if (idPesanan != -1) {
            tvPesananId.setText("Nomor Pesanan: #" + idPesanan);
            fetchPesananDetail();
            startPolling();
        }

        btnMenu.setOnClickListener(v -> {
            Intent intent = new Intent(MenungguKasirActivity.this, MainActivity.class);
            startActivity(intent);
            finishAffinity();
        });
    }

    private void fetchPesananDetail() {
        apiService.getPesananDetail(idPesanan).enqueue(new Callback<PesananResponse>() {
            @Override
            public void onResponse(Call<PesananResponse> call, Response<PesananResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    if (response.body().isSuccess() && response.body().getData() != null) {
                        double total = response.body().getData().getTotalHarga();
                        tvPesananTotal.setText(String.format("Total: Rp %,.0f", total));
                    }
                }
            }
            @Override
            public void onFailure(Call<PesananResponse> call, Throwable t) {}
        });
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
        apiService.checkKasirStatus(idPesanan).enqueue(new Callback<StatusResponse>() {
            @Override
            public void onResponse(Call<StatusResponse> call, Response<StatusResponse> response) {
                if (response.isSuccessful() && response.body() != null) {
                    if (response.body().isSuccess()) {
                        String status = response.body().getStatusPembayaran();
                        if ("Lunas".equalsIgnoreCase(status)) {
                            tvTitleStatus.setText("Pembayaran Lunas!");
                            tvDesc.setText("Pesanan Anda sedang kami proses ke dapur.");
                            btnMenu.setVisibility(View.VISIBLE);
                            if(handler != null) handler.removeCallbacks(pollingRunnable);
                        } else if ("Batal".equalsIgnoreCase(status) || "Gagal".equalsIgnoreCase(status)) {
                            tvTitleStatus.setText("Pesanan Dibatalkan");
                            tvDesc.setText("Pesanan ini telah dibatalkan.");
                            btnMenu.setVisibility(View.VISIBLE);
                            if(handler != null) handler.removeCallbacks(pollingRunnable);
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
