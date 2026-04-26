package com.example.codecraftstudioapp;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.ProgressBar;
import android.widget.RadioButton;
import android.widget.RadioGroup;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.example.codecraftstudioapp.models.CartItem;
import com.example.codecraftstudioapp.models.CheckoutRequest;
import com.example.codecraftstudioapp.models.CheckoutResponse;
import com.example.codecraftstudioapp.network.ApiClient;
import com.example.codecraftstudioapp.network.ApiService;
import com.example.codecraftstudioapp.utils.CartManager;
import com.google.android.material.textfield.TextInputEditText;

import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class PembayaranActivity extends AppCompatActivity {

    private TextInputEditText etNama, etPhone, etEmail, etCatatanOrang;
    private RadioGroup rgPayment, rgOrderType;
    private RadioButton rbQris, rbTakeAway;
    private TextView tvTotalCheckout;
    private Button btnProcessCheckout;
    private ProgressBar progressBar;
    
    private CartManager cartManager;
    private List<CartItem> cartList;
    private double totalAmount = 0;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_pembayaran);

        cartManager = new CartManager(this);
        cartList = cartManager.getCart();
        
        etNama = findViewById(R.id.etNama);
        etPhone = findViewById(R.id.etPhone);
        etEmail = findViewById(R.id.etEmail);
        etCatatanOrang = findViewById(R.id.etCatatanOrang);
        
        rgPayment = findViewById(R.id.rgPayment);
        rbQris = findViewById(R.id.rbQris);
        
        rgOrderType = findViewById(R.id.rgOrderType);
        rbTakeAway = findViewById(R.id.rbTakeAway);
        
        tvTotalCheckout = findViewById(R.id.tvTotalCheckout);
        btnProcessCheckout = findViewById(R.id.btnProcessCheckout);
        progressBar = findViewById(R.id.progressBar);

        totalAmount = getIntent().getDoubleExtra("TOTAL_AMOUNT", 0);
        tvTotalCheckout.setText(String.format("Total: Rp %,.0f", totalAmount));

        btnProcessCheckout.setOnClickListener(v -> processCheckout());
    }

    private void processCheckout() {
        String nama = etNama.getText().toString().trim();
        String phone = etPhone.getText().toString().trim();
        String email = etEmail.getText().toString().trim();
        String catatan = etCatatanOrang.getText().toString().trim();

        if (nama.isEmpty() || phone.isEmpty() || email.isEmpty()) {
            Toast.makeText(this, "Mohon lengkapi Nama, Telepon dan Email", Toast.LENGTH_SHORT).show();
            return;
        }

        String paymentMethod = rbQris.isChecked() ? "online" : "kasir";
        String orderType = rbTakeAway.isChecked() ? "take away" : "dine_in";

        CheckoutRequest request = new CheckoutRequest(
                nama, phone, email, paymentMethod, orderType, (int)totalAmount, catatan, cartList
        );

        progressBar.setVisibility(View.VISIBLE);
        btnProcessCheckout.setEnabled(false);

        ApiService apiService = ApiClient.getClient().create(ApiService.class);
        Call<CheckoutResponse> call = apiService.processCheckout(request);
        call.enqueue(new Callback<CheckoutResponse>() {
            @Override
            public void onResponse(Call<CheckoutResponse> call, Response<CheckoutResponse> response) {
                progressBar.setVisibility(View.GONE);
                btnProcessCheckout.setEnabled(true);
                
                if (response.isSuccessful() && response.body() != null) {
                    CheckoutResponse result = response.body();
                    if (result.isSuccess() || result.getMethod() != null) {
                        Toast.makeText(PembayaranActivity.this, "Pesanan Berhasil Dibuat", Toast.LENGTH_SHORT).show();
                        cartManager.clearCart();
                        
                        // Menentukan rute selanjutnya
                        if ("online".equals(result.getMethod())) {
                            Intent intent = new Intent(PembayaranActivity.this, MenungguQrisActivity.class);
                            intent.putExtra("QR_STRING", result.getQrString());
                            intent.putExtra("REFERENCE", result.getReference());
                            startActivity(intent);
                        } else {
                            Intent intent = new Intent(PembayaranActivity.this, MenungguKasirActivity.class);
                            intent.putExtra("ID_PESANAN", result.getIdPesanan());
                            startActivity(intent);
                        }
                        finishAffinity(); // Clear stack
                    } else {
                        Toast.makeText(PembayaranActivity.this, "Gagal Checkout: " + result.getMessage(), Toast.LENGTH_LONG).show();
                    }
                } else {
                    Toast.makeText(PembayaranActivity.this, "Format data salah / error server", Toast.LENGTH_SHORT).show();
                }
            }

            @Override
            public void onFailure(Call<CheckoutResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                btnProcessCheckout.setEnabled(true);
                Toast.makeText(PembayaranActivity.this, "Error: " + t.getMessage(), Toast.LENGTH_LONG).show();
            }
        });
    }
}
