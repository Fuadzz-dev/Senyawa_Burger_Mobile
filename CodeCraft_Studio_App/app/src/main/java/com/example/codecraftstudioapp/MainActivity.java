package com.example.codecraftstudioapp;

import android.content.Intent;
import android.os.Bundle;
import android.view.View;
import android.widget.Button;
import android.widget.ProgressBar;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.codecraftstudioapp.models.Menu;
import com.example.codecraftstudioapp.models.MenuResponse;
import com.example.codecraftstudioapp.network.ApiClient;
import com.example.codecraftstudioapp.network.ApiService;

import java.util.ArrayList;
import java.util.List;

import retrofit2.Call;
import retrofit2.Callback;
import retrofit2.Response;

public class MainActivity extends AppCompatActivity {

    private RecyclerView rvMenu;
    private MenuAdapter menuAdapter;
    private ProgressBar progressBar;
    private Button btnCart;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_menu);

        rvMenu = findViewById(R.id.rvMenu);
        progressBar = findViewById(R.id.progressBar);
        btnCart = findViewById(R.id.btnCart);

        rvMenu.setLayoutManager(new LinearLayoutManager(this));

        btnCart.setOnClickListener(v -> {
            Intent intent = new Intent(MainActivity.this, KeranjangActivity.class);
            startActivity(intent);
        });

        fetchMenus();
    }

    private void fetchMenus() {
        progressBar.setVisibility(View.VISIBLE);
        ApiService apiService = ApiClient.getClient().create(ApiService.class);
        Call<MenuResponse> call = apiService.getMenus();

        call.enqueue(new Callback<MenuResponse>() {
            @Override
            public void onResponse(Call<MenuResponse> call, Response<MenuResponse> response) {
                progressBar.setVisibility(View.GONE);
                if (response.isSuccessful() && response.body() != null) {
                    if (response.body().isSuccess()) {
                        List<Menu> menus = response.body().getData().getMenus();
                        menuAdapter = new MenuAdapter(MainActivity.this, menus);
                        rvMenu.setAdapter(menuAdapter);
                    } else {
                        Toast.makeText(MainActivity.this, "Gagal meload data menu", Toast.LENGTH_SHORT).show();
                    }
                }
            }

            @Override
            public void onFailure(Call<MenuResponse> call, Throwable t) {
                progressBar.setVisibility(View.GONE);
                Toast.makeText(MainActivity.this, "Error koneksi: " + t.getMessage(), Toast.LENGTH_SHORT).show();
            }
        });
    }
}