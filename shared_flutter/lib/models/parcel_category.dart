class ParcelCategory {
  const ParcelCategory({required this.id, required this.name});

  factory ParcelCategory.fromJson(Map<String, dynamic> json) {
    return ParcelCategory(
      id: '${json['id']}',
      name: '${json['name'] ?? json['title'] ?? 'Categoria'}',
    );
  }

  final String id;
  final String name;
}
