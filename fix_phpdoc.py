#!/usr/bin/env python3
"""
Script to add PHPDoc annotations for array parameters and return types
"""

import re
import sys

def add_phpdoc_to_method(content, line_num, method_signature):
    """Add PHPDoc annotation before a method"""
    lines = content.split('\n')
    
    # Check if PHPDoc already exists
    if line_num > 0 and '/**' in lines[line_num - 1]:
        return content
    
    # Determine what annotations are needed
    params = []
    return_annotation = None
    
    # Check for array parameters
    if 'array $' in method_signature:
        # Extract parameter names
        param_matches = re.findall(r'array \$(\w+)', method_signature)
        for param in param_matches:
            params.append(f"     * @param  array<string, mixed>  ${param}")
    
    # Check for array return type
    if '): array' in method_signature:
        return_annotation = "     * @return array<string, mixed>"
    
    # Build PHPDoc block
    if params or return_annotation:
        phpdoc_lines = ["    /**"]
        phpdoc_lines.extend(params)
        if return_annotation:
            phpdoc_lines.append(return_annotation)
        phpdoc_lines.append("     */")
        
        # Insert PHPDoc before the method
        indent = len(lines[line_num]) - len(lines[line_num].lstrip())
        phpdoc = '\n'.join(phpdoc_lines)
        lines.insert(line_num, phpdoc)
        
        return '\n'.join(lines)
    
    return content

# Read file
if len(sys.argv) < 2:
    print("Usage: python fix_phpdoc.py <file>")
    sys.exit(1)

filename = sys.argv[1]
with open(filename, 'r') as f:
    content = f.read()

# Find all method signatures with array parameters or return types
pattern = r'(protected|public|private)\s+function\s+\w+\([^)]*array[^)]*\):\s*array'
matches = list(re.finditer(pattern, content))

print(f"Found {len(matches)} methods needing PHPDoc annotations")

# Process matches in reverse order to maintain line numbers
for match in reversed(matches):
    line_num = content[:match.start()].count('\n')
    method_sig = match.group(0)
    print(f"Line {line_num + 1}: {method_sig[:60]}...")
    content = add_phpdoc_to_method(content, line_num, method_sig)

# Write back
with open(filename, 'w') as f:
    f.write(content)

print(f"Updated {filename}")

