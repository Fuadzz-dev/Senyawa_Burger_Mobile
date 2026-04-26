package com.example.codecraftstudioapp.models;

import java.util.List;

public class CheckoutRequest {
    private String nama;
    private String phone;
    private String email;
    private String paymentMethod;
    private String orderType;
    private int amount;
    private String catatan;
    private List<CartItem> cart;

    public CheckoutRequest(String nama, String phone, String email, String paymentMethod, String orderType, int amount, String catatan, List<CartItem> cart) {
        this.nama = nama;
        this.phone = phone;
        this.email = email;
        this.paymentMethod = paymentMethod;
        this.orderType = orderType;
        this.amount = amount;
        this.catatan = catatan;
        this.cart = cart;
    }
}
