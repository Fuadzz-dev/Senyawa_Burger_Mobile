package com.example.codecraftstudioapp;

import android.content.Intent;
import android.os.Bundle;
import android.widget.Button;
import android.widget.TextView;
import android.widget.Toast;

import androidx.appcompat.app.AppCompatActivity;
import androidx.recyclerview.widget.LinearLayoutManager;
import androidx.recyclerview.widget.RecyclerView;

import com.example.codecraftstudioapp.models.CartItem;
import com.example.codecraftstudioapp.utils.CartManager;

import java.util.List;

public class KeranjangActivity extends AppCompatActivity {

    private RecyclerView rvCart;
    private TextView tvTotal;
    private Button btnCheckout;
    private CartAdapter cartAdapter;
    private CartManager cartManager;
    private List<CartItem> cartList;
    private double totalAmount = 0;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_keranjang);

        rvCart = findViewById(R.id.rvCart);
        tvTotal = findViewById(R.id.tvTotal);
        btnCheckout = findViewById(R.id.btnCheckout);

        rvCart.setLayoutManager(new LinearLayoutManager(this));

        cartManager = new CartManager(this);
        cartList = cartManager.getCart();

        if (cartList.isEmpty()) {
            Toast.makeText(this, "Keranjang Anda Kosong", Toast.LENGTH_SHORT).show();
            btnCheckout.setEnabled(false);
        } else {
            cartAdapter = new CartAdapter(this, cartList);
            rvCart.setAdapter(cartAdapter);
            calculateTotal();
        }

        btnCheckout.setOnClickListener(v -> {
            Intent intent = new Intent(KeranjangActivity.this, PembayaranActivity.class);
            intent.putExtra("TOTAL_AMOUNT", totalAmount);
            startActivity(intent);
        });
    }

    private void calculateTotal() {
        totalAmount = 0;
        for (CartItem item : cartList) {
            totalAmount += (item.getPrice() * item.getQty());
        }
        tvTotal.setText(String.format("Rp %,.0f", totalAmount));
    }
    
    @Override
    protected void onResume() {
        super.onResume();
        // Update cart if changes happened
        cartList = cartManager.getCart();
        if (cartList.isEmpty()) {
            btnCheckout.setEnabled(false);
        } else {
            btnCheckout.setEnabled(true);
            calculateTotal();
            if(cartAdapter != null){
                cartAdapter.notifyDataSetChanged();
            } else {
                cartAdapter = new CartAdapter(this, cartList);
                rvCart.setAdapter(cartAdapter);
            }
        }
    }
}
