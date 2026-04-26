package com.example.codecraftstudioapp.models;

import com.google.gson.annotations.SerializedName;
import java.io.Serializable;

public class Menu implements Serializable {
    @SerializedName("id_menu")
    private int idMenu;

    @SerializedName("nama_menu")
    private String namaMenu;

    @SerializedName("Deskripsi")
    private String deskripsi;

    @SerializedName("harga")
    private double harga;

    @SerializedName("Kategori")
    private String kategori;

    @SerializedName("foto_url")
    private String fotoUrl;

    @SerializedName("status_tersedia")
    private int statusTersedia;

    public int getIdMenu() { return idMenu; }
    public String getNamaMenu() { return namaMenu; }
    public String getDeskripsi() { return deskripsi; }
    public double getHarga() { return harga; }
    public String getKategori() { return kategori; }
    /** URL gambar, misalnya: http://10.0.2.2:8000/api/menu/3/foto */
    public String getFotoUrl() { return fotoUrl; }
    /** @deprecated Gunakan getFotoUrl() — field ini sudah tidak dipakai */
    public String getFoto() { return fotoUrl; }
    public boolean isTersedia() { return statusTersedia == 1; }
}

