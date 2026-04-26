package com.example.codecraftstudioapp.models;

import com.google.gson.annotations.SerializedName;

public class DetailPesanan {
    @SerializedName("id_detail")
    private int idDetail;

    @SerializedName("id_menu")
    private int idMenu;

    @SerializedName("jumlah")
    private int jumlah;

    @SerializedName("harga_satuan")
    private double hargaSatuan;

    @SerializedName("kustomisasi")
    private String kustomisasi;

    @SerializedName("menu")
    private Menu menu;

    public int getIdDetail() { return idDetail; }
    public int getIdMenu() { return idMenu; }
    public int getJumlah() { return jumlah; }
    public double getHargaSatuan() { return hargaSatuan; }
    public String getKustomisasi() { return kustomisasi; }
    public Menu getMenu() { return menu; }
}
