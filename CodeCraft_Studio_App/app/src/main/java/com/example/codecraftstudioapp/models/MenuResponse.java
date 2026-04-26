package com.example.codecraftstudioapp.models;

import java.util.List;
import java.util.Map;

public class MenuResponse {
    private boolean success;
    private MenuData data;

    public boolean isSuccess() { return success; }
    public MenuData getData() { return data; }

    public static class MenuData {
        private List<String> kategoriList;
        private List<Menu> menus;

        public List<String> getKategoriList() { return kategoriList; }
        public List<Menu> getMenus() { return menus; }
    }
}
