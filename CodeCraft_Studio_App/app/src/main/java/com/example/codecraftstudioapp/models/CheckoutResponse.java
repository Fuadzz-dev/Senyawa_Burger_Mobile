package com.example.codecraftstudioapp.models;

public class CheckoutResponse {
    private boolean success;
    private String message;
    private String method;
    private int id_pesanan;
    private String qrString;
    private String reference;

    public boolean isSuccess() { return success; }
    public String getMessage() { return message; }
    public String getMethod() { return method; }
    public int getIdPesanan() { return id_pesanan; }
    public String getQrString() { return qrString; }
    public String getReference() { return reference; }
}
