function renderTree(treeData) {
  const width = 800;
  const dx = 100;
  const dy = 360;

  const treeLayout = d3.tree().nodeSize([dx, dy]);
  const root = d3.hierarchy(treeData);
  treeLayout(root);

  const svg = d3.select("#tree").append("svg")
    .attr("viewBox", [-dy / 3, -dx, width, dx * root.height + 200])
    .style("font", "12px sans-serif")
    .style("user-select", "none");

  // HIER skaliert:
  const scaleFactor = 0.5; 
  const g = svg.append("g")
    .attr("transform", `translate(80,0) scale(${scaleFactor})`);

  // Lines & Nodes bleiben unverändert
  const link = g.append("g")
    .selectAll("path")
    .data(root.links())
    .join("path")
    .attr("d", d3.linkHorizontal().x(d => d.y).y(d => d.x))
    .attr("fill", "none")
    .attr("stroke", "#555");

  const node = g.append("g")
    .selectAll("g")
    .data(root.descendants())
    .join("g")
    .attr("transform", d => `translate(${d.y},${d.x})`);

  node.append("circle")
    .attr("r", 6)
    .attr("fill", d => d.children ? "#999" : "#2ca");

  node.append("text")
    .attr("dy", "0.31em")
    .attr("x", d => d.children ? -30 : 30)
    .attr("text-anchor", d => d.children ? "end" : "start")
    .text(d => d.data.name + " – " + (d.data.text || ""))
    .clone(true).lower()
      .attr("stroke", "white");
}
