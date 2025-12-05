# ER Diagram Files

This folder contains PlantUML files for generating Entity Relationship Diagrams.

## Files

- `database_er_diagram.puml` - Complete ER diagram for the Email List Subscription Project

## How to Use

### Option 1: Online PlantUML Server
1. Go to http://www.plantuml.com/plantuml/uml/
2. Copy the contents of `database_er_diagram.puml`
3. Paste into the online editor
4. The diagram will be generated automatically

### Option 2: PlantUML Local Installation
1. Install PlantUML: http://plantuml.com/download
2. Install Graphviz (required): https://graphviz.org/download/
3. Run: `plantuml database_er_diagram.puml`
4. Output will be generated as PNG or SVG

### Option 3: VS Code Extension
1. Install "PlantUML" extension in VS Code
2. Open the `.puml` file
3. Press `Alt+D` to preview the diagram

### Option 4: IntelliJ IDEA / PHPStorm
1. Install PlantUML plugin
2. Open the `.puml` file
3. The diagram will render automatically

## Diagram Details

The ER diagram shows:
- **3 Main Entities**: departments, employees, email_subscriptions
- **Relationships**: 
  - Departments to Employees (One-to-Many)
  - Each department has one head (One-to-One)
  - Each department has multiple supervisors (One-to-Many)
- **Primary Keys**: Marked with <<PK>>
- **Foreign Keys**: Marked with <<FK>>
- **Unique Constraints**: Marked with <<UNIQUE>>

## Notes

- The diagram includes business rule annotations
- All relationships are clearly labeled
- Cardinality is shown with appropriate notation

