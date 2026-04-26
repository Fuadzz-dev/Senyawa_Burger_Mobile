package com.example.codecraftstudioapp.utils;

import android.content.Context;
import android.content.SharedPreferences;

import com.example.codecraftstudioapp.models.CartItem;
import com.google.gson.Gson;
import com.google.gson.reflect.TypeToken;

import java.lang.reflect.Type;
import java.util.ArrayList;
import java.util.List;

public class CartManager {

    private static final String PREF_NAME = "CodeCraftCart";
    private static final String KEY_CART = "cart_items";

    private SharedPreferences prefs;
    private Gson gson;

    public CartManager(Context context) {
        prefs = context.getSharedPreferences(PREF_NAME, Context.MODE_PRIVATE);
        gson = new Gson();
    }

    public void addToCart(CartItem item) {
        List<CartItem> cart = getCart();
        boolean exists = false;
        for (CartItem c : cart) {
            if (c.getId() == item.getId()) {
                c.setQty(c.getQty() + item.getQty());
                if(item.getNotes() != null && !item.getNotes().isEmpty()){
                    c.setNotes(item.getNotes());
                }
                exists = true;
                break;
            }
        }
        if (!exists) {
            cart.add(item);
        }
        saveCart(cart);
    }

    public List<CartItem> getCart() {
        String json = prefs.getString(KEY_CART, null);
        if (json == null) {
            return new ArrayList<>();
        }
        Type type = new TypeToken<ArrayList<CartItem>>() {}.getType();
        return gson.fromJson(json, type);
    }

    public void saveCart(List<CartItem> cart) {
        String json = gson.toJson(cart);
        prefs.edit().putString(KEY_CART, json).apply();
    }

    public void clearCart() {
        prefs.edit().remove(KEY_CART).apply();
    }
}
