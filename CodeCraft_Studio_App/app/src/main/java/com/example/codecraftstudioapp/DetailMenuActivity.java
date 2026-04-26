package com.example.codecraftstudioapp;

import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.ProgressBar;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;

import com.bumptech.glide.Glide;
import com.example.codecraftstudioapp.models.CartItem;
import com.example.codecraftstudioapp.models.DetailMenuResponse;
import com.example.codecraftstudioapp.models.Menu;
import com.example.codecraftstudioapp.network.ApiClient;
import com.example.codecraftstudioapp.network.ApiService;
import com.example.codecraftstudioapp.utils.CartManager;
import com.google.android.material.textfield.TextInputEditText;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class DetailMenuActivity extends AppCompatActivity {

    private ImageView imgMenu;
    private TextView tvNamaMenu, tvHarga, tvKategori, tvDeskripsi, tvQty;
    private TextInputEditText etCatatan;
    private Button btnMinus, btnPlus, btnAddToCart;
    private ProgressBar progressBar;
    private int currentQty = 1;
    private Menu currentMenu = null;
    private CartManager cartManager;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_detail_menu);

        cartManager = new CartManager(this);

        imgMenu = findViewById(R.id.imgMenu);
        tvNamaMenu = findViewById(R.id.tvNamaMenu);
        tvHarga = findViewById(R.id.tvHarga);
        tvKategori = findViewById(R.id.tvKategori);
        tvDeskripsi = findViewById(R.id.tvDeskripsi);
        tvQty = findViewById(R.id.tvQty);
        etCatatan = findViewById(R.id.etCatatan);
        btnMinus = findViewById(R.id.btnMinus);
        btnPlus = findViewById(R.id.btnPlus);
        btnAddToCart = findViewById(R.id.btnAddToCart);
        progressBar = findViewById(R.id.progressBar);

        int idMenu = getIntent().getIntExtra("ID_MENU", -1);
        if(idMenu != -1) {
            fetchDetailMenu(idMenu);
        }

        btnMinus.setOnClickListener(v -> {
            if (currentQty > 1) {
                currentQty--;
                tvQty.setText(String.valueOf(currentQty));
            }
        });

        btnPlus.setOnClickListener(v -> {
            currentQty++;
            tvQty.setText(String.valueOf(currentQty));
        });

        btnAddToCart.setOnClickListener(v -> {
            if (currentMenu != null) {
                String notes = etCatatan.getText().toString();
                CartItem item = new CartItem(
                        currentMenu.getIdMenu(),
                        currentMenu.getNamaMenu(),
                        currentMenu.getHarga(),
                        currentQty,
                        notes,
                        currentMenu.getFoto()
                );
                cartManager.addToCart(item);
                Toast.makeText(DetailMenuActivity.this, "Berhasil masuk keranjang!", Toast.LENGTH_SHORT).show();
                finish();
            }
        });
    }

    private void fetchDetailMenu(int idMenu) {
        progressBar.setVisibility(View.VISIBLE);
        ApiService apiService = ApiClient.getClient().create(ApiService.class);
        Call<DetailMenuResponse> call = apiService.getDetailMenu(idMenu);
        call.enqueue(new Callback<DetailMenuResponse>() {
            @Override
            public void onResponse(Call<DetailMenuResponse> call, Response<DetailMenuResponse> response) {
                progressBar.setVisibility(View.GONE);
                if(response.isSuccessful() && response.body() != null) {
                    if(response.body().isSuccess()){
                        currentMenu = response.body().getData();
                        bindData(currentMenu);
                    } else {
                        Toast.makeText(DetailMenuActivity.this, "Menu tidak ditemukan", Toast.LENGTH_SHORT).show();
                    }
                }
            }
            @Override
            public void onFailure(Call<DetailMenuResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                Toast.makeText(DetailMenuActivity.this, "Gagal meload menu", Toast.LENGTH_SHORT).show();
            }
        });
    }

    private void bindData(Menu menu) {
        tvNamaMenu.setText(menu.getNamaMenu());
        tvHarga.setText(String.format("Rp %,.0f", menu.getHarga()));
        tvKategori.setText(menu.getKategori());
        tvDeskripsi.setText(menu.getDeskripsi());

        if (menu.getFotoUrl() != null && !menu.getFotoUrl().isEmpty()) {
            Glide.with(this)
                    .load(menu.getFotoUrl())
                    .placeholder(R.mipmap.ic_launcher)
                    .error(R.mipmap.ic_launcher)
                    .into(imgMenu);
        } else {
            imgMenu.setImageResource(R.mipmap.ic_launcher);
        }
    }
}
